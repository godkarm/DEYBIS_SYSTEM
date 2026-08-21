<?php
class ConfiguracionController {
    public function recalcularStock(): void {
        Auth::requireSeccion('configuracion');
        try {
            $n = (new MovimientoModel())->recalcularCache();
            Response::ok(null, "Stock recalculado para {$n} relación(es) producto-cliente.");
        } catch (Throwable $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
        }
    }

    public function validarIntegridad(): void {
        Auth::requireSeccion('configuracion');
        // Validación básica de coherencia entre tablas
        $db     = Database::getInstance()->getConnection();
        $errores = [];

        // Caché desincronizado
        $stmt = $db->query("
            SELECT pc.id, c.codigo AS cliente, p.codigo,
                   pc.stock_actual AS cache,
                   COALESCE(
                     SUM(CASE m.tipo
                       WHEN 'INGRESO'         THEN  m.cantidad
                       WHEN 'AJUSTE_POSITIVO' THEN  m.cantidad
                       WHEN 'SALIDA'          THEN -m.cantidad
                       WHEN 'AJUSTE_NEGATIVO' THEN -m.cantidad
                       WHEN 'AJUSTE'          THEN  m.cantidad
                       ELSE 0 END), 0
                   ) AS calculado
            FROM producto_cliente pc
            JOIN clientes  c ON c.id = pc.id_cliente
            JOIN productos p ON p.id = pc.id_producto
            LEFT JOIN movimientos m ON m.id_pc = pc.id
            GROUP BY pc.id
            HAVING ABS(cache - GREATEST(0, calculado)) > 0.01
        ");
        foreach ($stmt->fetchAll() as $r) {
            $errores[] = "Caché desincronizado {$r['cliente']}/{$r['codigo']}: caché={$r['cache']}, esperado={$r['calculado']}. Ejecute Recalcular Stock.";
        }

        // Clientes inactivos con stock
        $stmt = $db->query("
            SELECT c.codigo, SUM(pc.stock_actual) AS total
            FROM clientes c
            JOIN producto_cliente pc ON pc.id_cliente = c.id
            WHERE c.estado = 'INACTIVO'
            GROUP BY c.id HAVING total > 0
        ");
        foreach ($stmt->fetchAll() as $r) {
            $errores[] = "Cliente INACTIVO {$r['codigo']} tiene stock pendiente: {$r['total']}.";
        }

        Response::ok(['errores' => $errores]);
    }

    public function inicializar(): void {
        Auth::requireSeccion('configuracion');
        Auth::requireRol(['ADMINISTRADOR']);
        // Ejecutar el schema si las tablas no existen (idempotente con IF NOT EXISTS)
        Response::ok(null, 'El sistema ya está inicializado. Use el script SQL para reinstalar.');
    }
    public function descargarMaestro(): void {
    Auth::requireSeccion('configuracion');

    $grupos = array_keys(PREFIJOS_POR_GRUPO);
    $db     = Database::getInstance()->getConnection();
    $unidades = $db->query("SELECT nombre FROM unidades ORDER BY nombre")
                   ->fetchAll(PDO::FETCH_COLUMN);

    $nombre = 'Maestro_Catalogo_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$nombre}\"");

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

    // sep=, le indica a Excel que el separador es coma
    fwrite($out, "sep=,\r\n");

    // Comentarios como texto plano
    fwrite($out, "# ARCHIVO MAESTRO - DEYBIS SYSTEM\r\n");
    fwrite($out, "# Columnas obligatorias: nombre | grupo | unidad\r\n");
    fwrite($out, "# Columna opcional: stock_min (default 0) | clientes (codigos separados por |)\r\n");
    fwrite($out, "# Grupos validos: " . implode(" / ", $grupos) . "\r\n");
    fwrite($out, "# Unidades validas: " . implode(" / ", $unidades) . "\r\n");
    fwrite($out, "# Las filas que empiezan con # son ignoradas\r\n");
    fwrite($out, "# NO borres ni modifiques la fila de cabecera de abajo\r\n");
    fwrite($out, "\r\n");

    // Cabecera de columnas
    fwrite($out, "nombre,grupo,unidad,stock_min,clientes\r\n");

    // Filas de ejemplo
    fwrite($out, "Guante de seguridad talla M,EPP,Par,10,CLI001|CLI002\r\n");
    fwrite($out, "Aceite lubricante 1L,Ferreteria,Litro,5,\r\n");
    fwrite($out, "Papel bond A4,Utiles de oficina,Caja,2,\r\n");

    fclose($out);
    exit;
}

public function importarCatalogo(): void {
    Auth::requireSeccion('configuracion');
    Auth::requireRol(['ADMINISTRADOR']);

    if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        Response::error('No se recibió ningún archivo válido.');
    }

    $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        Response::error('Solo se permiten archivos .csv');
    }

    $handle = fopen($_FILES['archivo']['tmp_name'], 'r');
    if (!$handle) Response::error('No se pudo leer el archivo.');

    // Detectar y eliminar BOM UTF-8
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") rewind($handle);

    $db           = Database::getInstance()->getConnection();
    $productoModel = new ProductoModel();
    $clienteModel  = new ClienteModel();

    $grupos   = PREFIJOS_POR_GRUPO;                          // ['EPP'=>'EPP',...]
    $listas   = $productoModel->listas();
    $unidades = $listas['unidades'];

    $cabecera    = null;
    $importados  = 0;
    $omitidos    = 0;
    $errores     = [];
    $fila        = 0;

    $db->beginTransaction();
    try {
        while (($row = fgetcsv($handle)) !== false) {
            $fila++;

            // Ignorar sep=, comentarios y filas vacías
            if (empty($row) || empty($row[0])) continue;
            $first = trim($row[0]);
            if (str_starts_with($first, '#') || str_starts_with($first, 'sep=')) continue;

            // Primera fila válida = cabecera
            if ($cabecera === null) {
                $cabecera = array_map('trim', $row);
                continue;
            }

            // Mapear columnas
            $data = [];
            foreach ($cabecera as $i => $col) {
                $data[$col] = trim($row[$i] ?? '');
            }

            $nombre   = $data['nombre']    ?? '';
            $grupo    = $data['grupo']     ?? '';
            $unidad   = $data['unidad']    ?? 'Unidades';
            $stockMin = (float)($data['stock_min'] ?? 0);
            $cliRaw   = $data['clientes']  ?? '';

            // Validaciones
            if (!$nombre) {
                $errores[] = "Fila {$fila}: nombre vacío — omitida.";
                $omitidos++; continue;
            }
            if (!isset($grupos[$grupo])) {
                $errores[] = "Fila {$fila}: grupo '{$grupo}' inválido — omitida.";
                $omitidos++; continue;
            }
            if (!in_array($unidad, $unidades, true)) {
                $unidad = 'Unidades'; // fallback
            }

            // IDs de grupo y unidad
            $idGrupo  = (int)$db->query(
                "SELECT id FROM grupos WHERE nombre = " . $db->quote($grupo)
            )->fetchColumn();
            $idUnidad = (int)$db->query(
                "SELECT id FROM unidades WHERE nombre = " . $db->quote($unidad)
            )->fetchColumn();

            if (!$idGrupo || !$idUnidad) {
                $errores[] = "Fila {$fila}: no se encontró grupo o unidad en BD — omitida.";
                $omitidos++; continue;
            }

            // Generar código correlativo
            $prefijo = $grupos[$grupo];
            $codigo  = $productoModel->generarCodigo($prefijo);

            // Insertar producto
            $idProd = $productoModel->create($codigo, $nombre, $idUnidad, $idGrupo);

            // Habilitar para clientes indicados
            if ($cliRaw) {
                $codigos = array_filter(array_map('trim', explode('|', $cliRaw)));
                foreach ($codigos as $codCli) {
                    $cli = $clienteModel->findByCodigo($codCli);
                    if ($cli && $cli['estado'] === 'ACTIVO') {
                        $productoModel->habilitar($cli['id'], $idProd, $stockMin);
                    }
                }
            }

            $importados++;
        }

        fclose($handle);
        $db->commit();

        Response::ok([
            'importados' => $importados,
            'omitidos'   => $omitidos,
            'errores'    => $errores,
        ], "Importación completada: {$importados} producto(s) importado(s), {$omitidos} omitido(s).");

    } catch (Throwable $e) {
        $db->rollBack();
        fclose($handle);
        Response::error('Error durante la importación: ' . $e->getMessage(), 500);
    }
}
public function resetearSistema(): void {
    Auth::requireSeccion('configuracion');
    Auth::requireRol(['ADMINISTRADOR']);

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $confirmar = trim($body['confirmar'] ?? '');

    if ($confirmar !== 'RESETEAR') {
        Response::error('Debes confirmar escribiendo RESETEAR.');
    }

    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();
    try {
        // Orden correcto respetando FK
        $db->exec("DELETE FROM sesiones");
        $db->exec("DELETE FROM permisos_usuario");
        $db->exec("DELETE FROM movimientos");
        $db->exec("DELETE FROM producto_cliente");
        $db->exec("DELETE FROM productos");
        $db->exec("DELETE FROM correlativos");
        $db->exec("DELETE FROM clientes");

        // Reinsertar correlativos en 0
        $db->exec("
            INSERT INTO correlativos (prefijo, ultimo_num)
            SELECT prefijo, 0 FROM grupos
        ");

        $db->commit();
        Response::ok(null, 'Sistema reseteado correctamente. Catálogo, clientes, movimientos y correlativos reiniciados.');
    } catch (Throwable $e) {
        $db->rollBack();
        Response::error('Error al resetear: ' . $e->getMessage(), 500);
    }
}
}