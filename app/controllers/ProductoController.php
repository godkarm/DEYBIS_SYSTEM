<?php
class ProductoController {
    private ProductoModel $model;
    private ClienteModel  $clienteModel;

    public function __construct() {
        $this->model        = new ProductoModel();
        $this->clienteModel = new ClienteModel();
    }

    public function catalogo(): void {
        Auth::requireSeccion('productos');
        Response::ok($this->model->catalogo());
    }

    public function listas(): void {
        Auth::requireLogin();
        Response::ok($this->model->listas());
    }

    public function prefijos(): void {
        Auth::requireLogin();
        Response::ok(PREFIJOS_POR_GRUPO);
    }

    public function store(): void {
        Auth::requireSeccion('productos');
        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $grupo = trim($body['grupo'] ?? '');
        $nombre = trim($body['nombre'] ?? '');

        if (!$grupo || !$nombre) {
            Response::error('Nombre y grupo son obligatorios.');
        }
        if (!isset(PREFIJOS_POR_GRUPO[$grupo])) {
            Response::error("Grupo inválido: {$grupo}");
        }

        // Buscar IDs de unidad y grupo
        $listas = $this->model->listas();
        $unidadNombre = $body['unidad'] ?? 'Unidades';
        if (!in_array($unidadNombre, $listas['unidades'], true)) {
            Response::error("Unidad inválida: {$unidadNombre}");
        }

        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            $idGrupo  = (int)$db->query("SELECT id FROM grupos WHERE nombre = " . $db->quote($grupo))->fetchColumn();
            $idUnidad = (int)$db->query("SELECT id FROM unidades WHERE nombre = " . $db->quote($unidadNombre))->fetchColumn();

            $prefijo = PREFIJOS_POR_GRUPO[$grupo];
            $codigo  = $this->model->generarCodigo($prefijo);
            $idProd  = $this->model->create($codigo, $nombre, $idUnidad, $idGrupo);

            // Habilitar para clientes indicados (opcional)
            $clientes = $body['clientes'] ?? [];
            foreach ($clientes as $codigoCli) {
                $cli = $this->clienteModel->findByCodigo($codigoCli);
                if ($cli && $cli['estado'] === 'ACTIVO') {
                    $this->model->habilitar($cli['id'], $idProd, (float)($body['stock_min'] ?? 0));
                }
            }

            $db->commit();
            Response::ok(['codigo' => $codigo], "Producto registrado. Código asignado: {$codigo}");
        } catch (Throwable $e) {
            $db->rollBack();
            Response::error('Error al registrar producto: ' . $e->getMessage(), 500);
        }
    }

    public function habilitar(): void {
        Auth::requireSeccion('productos');
        $body     = json_decode(file_get_contents('php://input'), true) ?? [];
        $codCli   = strtoupper(trim($body['cliente'] ?? ''));
        $codProd  = strtoupper(trim($body['producto'] ?? ''));
        $stockMin = (float)($body['stock_min'] ?? 0);

        $cli  = $this->clienteModel->findByCodigo($codCli);
        if (!$cli) Response::error('Cliente no encontrado.', 404);
        if ($cli['estado'] !== 'ACTIVO') Response::error('El cliente está inactivo.');

        $prod = $this->model->findByCodigo($codProd);
        if (!$prod) Response::error('Producto no encontrado en el catálogo.', 404);

        if ($this->model->findPC($cli['id'], $prod['id'])) {
            Response::error('El producto ya está habilitado para este cliente.');
        }

        $this->model->habilitar($cli['id'], $prod['id'], $stockMin);
        Response::ok(null, 'Producto habilitado para el cliente.');
    }

    public function deshabilitar(): void {
        Auth::requireSeccion('productos');
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $codCli  = strtoupper(trim($body['cliente'] ?? ''));
        $codProd = strtoupper(trim($body['producto'] ?? ''));

        $cli  = $this->clienteModel->findByCodigo($codCli);
        $prod = $this->model->findByCodigo($codProd);
        if (!$cli || !$prod) Response::error('Cliente o producto no encontrado.', 404);

        $stock = $this->model->stockPC($cli['id'], $prod['id']);
        if ($stock > 0) {
            Response::error("No se puede deshabilitar: stock pendiente ({$stock}).");
        }

        $this->model->deshabilitar($cli['id'], $prod['id']);
        Response::ok(null, 'Producto deshabilitado para el cliente.');
    }

    public function buscar(): void {
        Auth::requireSeccion('productos');
        $texto = trim($_GET['q'] ?? '');
        if (!$texto) Response::ok([]);

        $idCliente = null;
        $codCli    = Auth::resolverCliente($_GET['cliente'] ?? null);
        if ($codCli) {
            $cli = $this->clienteModel->findByCodigo($codCli);
            $idCliente = $cli['id'] ?? null;
        }

        Response::ok($this->model->buscarPorNombre($texto, $idCliente));
    }
}
