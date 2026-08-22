<?php
class RequerimientoController {
    private RequerimientoModel $model;
    private ClienteModel       $clienteModel;

    public function __construct() {
        $this->model        = new RequerimientoModel();
        $this->clienteModel = new ClienteModel();
    }

    public function index(): void {
        Auth::requireSeccion('requerimientos');
        $idCliente = null;

        if (Session::rol() === 'CLIENTE') {
            $cli = $this->clienteModel->findByCodigo(Session::clienteAsociado());
            $idCliente = $cli['id'] ?? null;
        } else {
            $codCli = Auth::resolverCliente($_GET['cliente'] ?? null);
            if ($codCli) {
                $cli = $this->clienteModel->findByCodigo($codCli);
                $idCliente = $cli['id'] ?? null;
            }
        }

        $estado = $_GET['estado'] ?? null;
        Response::ok($this->model->all($idCliente, $estado ?: null));
    }

    public function show(): void {
        Auth::requireSeccion('requerimientos');
        $id  = (int)($_GET['id'] ?? 0);
        $req = $this->model->findById($id);
        if (!$req) Response::error('Requerimiento no encontrado.', 404);

        // CLIENTE solo ve los suyos
        if (Session::rol() === 'CLIENTE') {
            $cli = $this->clienteModel->findByCodigo(Session::clienteAsociado());
            if ($req['id_cliente'] != ($cli['id'] ?? 0)) {
                Response::error('Sin acceso a este requerimiento.', 403);
            }
        }

        Response::ok([
            'requerimiento' => $req,
            'items'         => $this->model->items($id),
        ]);
    }

    public function store(): void {
        Auth::requireSeccion('requerimientos');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // Solo CLIENTE puede crear
        if (Session::rol() !== 'CLIENTE') {
            Response::error('Solo el usuario CLIENTE puede crear requerimientos.');
        }

        $cli = $this->clienteModel->findByCodigo(Session::clienteAsociado());
        if (!$cli || $cli['estado'] !== 'ACTIVO') {
            Response::error('Cliente inactivo o no encontrado.');
        }

        $items = $body['items'] ?? [];
        if (empty($items)) {
            Response::error('Debe agregar al menos un producto al requerimiento.');
        }

        // Validar items y obtener id_pc
        $db        = Database::getInstance()->getConnection();
        $itemsValidos = [];
        foreach ($items as $item) {
            $codigo   = strtoupper(trim($item['codigo'] ?? ''));
            $cantidad = (float)($item['cantidad'] ?? 0);
            if (!$codigo || $cantidad <= 0) continue;

            $stmt = $db->prepare("
                SELECT pc.id, pc.stock_actual, p.nombre
                FROM producto_cliente pc
                JOIN productos p ON p.id = pc.id_producto
                WHERE pc.id_cliente = ? AND p.codigo = ?
            ");
            $stmt->execute([$cli['id'], $codigo]);
            $pc = $stmt->fetch();

            if (!$pc) {
                Response::error("Producto {$codigo} no habilitado para su cliente.");
            }

            $itemsValidos[] = [
                'id_pc'    => $pc['id'],
                'cantidad' => $cantidad,
                'obs'      => $item['obs'] ?? null,
            ];
        }

        if (empty($itemsValidos)) {
            Response::error('No hay ítems válidos en el requerimiento.');
        }

        try {
            $idReq = $this->model->crear(
                $cli['id'],
                Session::idUsuario(),
                $body['observaciones'] ?? '',
                $itemsValidos
            );
            $req = $this->model->findById($idReq);
            Response::ok(['numero' => $req['numero']], "Requerimiento {$req['numero']} creado correctamente.");
        } catch (Throwable $e) {
            Response::error('Error al crear requerimiento: ' . $e->getMessage(), 500);
        }
    }

    public function aprobar(): void {
        Auth::requireSeccion('requerimientos');
        Auth::requireRol(['ADMINISTRADOR', 'ALMACENERO']);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);

        $req = $this->model->findById($id);
        if (!$req) Response::error('Requerimiento no encontrado.', 404);
        if ($req['estado'] !== 'PENDIENTE') {
            Response::error("El requerimiento está en estado {$req['estado']} y no puede aprobarse.");
        }

        $this->model->cambiarEstado($id, 'APROBADO');
        Response::ok(null, "Requerimiento {$req['numero']} aprobado.");
    }

    public function rechazar(): void {
        Auth::requireSeccion('requerimientos');
        Auth::requireRol(['ADMINISTRADOR', 'ALMACENERO']);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);

        $req = $this->model->findById($id);
        if (!$req) Response::error('Requerimiento no encontrado.', 404);
        if (!in_array($req['estado'], ['PENDIENTE','APROBADO'])) {
            Response::error("El requerimiento no puede rechazarse en estado {$req['estado']}.");
        }

        $this->model->cambiarEstado($id, 'RECHAZADO');
        Response::ok(null, "Requerimiento {$req['numero']} rechazado.");
    }

    public function despachar(): void {
        Auth::requireSeccion('requerimientos');
        Auth::requireRol(['ADMINISTRADOR', 'ALMACENERO']);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);

        $req = $this->model->findById($id);
        if (!$req) Response::error('Requerimiento no encontrado.', 404);
        if ($req['estado'] !== 'APROBADO') {
            Response::error("Solo se pueden despachar requerimientos APROBADOS.");
        }

        $despachos = $body['despachos'] ?? [];
        if (empty($despachos)) Response::error('No hay despachos definidos.');

        try {
            $this->model->despachar($id, $despachos, Session::idUsuario());
            Response::ok(null, "Requerimiento {$req['numero']} despachado. Stock actualizado.");
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}