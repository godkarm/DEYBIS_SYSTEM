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
}
