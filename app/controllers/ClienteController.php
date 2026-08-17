<?php
class ClienteController {
    private ClienteModel $model;

    public function __construct() {
        $this->model = new ClienteModel();
    }

    public function index(): void {
        Auth::requireSeccion('clientes');
        $soloActivos = ($_GET['activos'] ?? '0') === '1';
        $clientes    = $this->model->all($soloActivos);

        // Rol CLIENTE solo ve su propio cliente
        if (Session::rol() === 'CLIENTE') {
            $codigo   = Session::clienteAsociado();
            $clientes = array_filter($clientes, fn($c) => $c['codigo'] === $codigo);
            $clientes = array_values($clientes);
        }

        Response::ok($clientes);
    }

    public function store(): void {
        Auth::requireSeccion('clientes');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $codigo = strtoupper(trim($body['codigo'] ?? ''));
        $nombre = trim($body['nombre'] ?? '');

        if (!$codigo || !$nombre) {
            Response::error('Código y nombre son obligatorios.');
        }
        if (strlen($nombre) < 2) {
            Response::error('El nombre debe tener al menos 2 caracteres.');
        }
        if ($this->model->findByCodigo($codigo)) {
            Response::error('Ya existe un cliente con ese código.');
        }

        $this->model->create($codigo, $nombre);
        Response::ok(null, 'Cliente registrado correctamente.');
    }

    public function cambiarEstado(): void {
        Auth::requireSeccion('clientes');
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $codigo = strtoupper(trim($body['codigo'] ?? ''));
        $estado = strtoupper(trim($body['estado'] ?? ''));

        if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
            Response::error('Estado inválido.');
        }

        $cliente = $this->model->findByCodigo($codigo);
        if (!$cliente) Response::error('Cliente no encontrado.', 404);

        if ($estado === 'INACTIVO') {
            $stock = $this->model->stockTotal($cliente['id']);
            if ($stock > 0) {
                Response::error("No se puede inactivar: el cliente tiene stock pendiente ({$stock}).");
            }
        }

        $this->model->cambiarEstado($cliente['id'], $estado);
        Response::ok(null, "Cliente {$estado} correctamente.");
    }
    public function actualizar(): void {
    Auth::requireSeccion('clientes');
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $codigo = strtoupper(trim($body['codigo'] ?? ''));
    $nombre = trim($body['nombre'] ?? '');
    $estado = strtoupper(trim($body['estado'] ?? 'ACTIVO'));

    if (!$codigo || !$nombre) {
        Response::error('Código y nombre son obligatorios.');
    }
    $cliente = $this->model->findByCodigo($codigo);
    if (!$cliente) Response::error('Cliente no encontrado.', 404);

    $this->model->actualizar($cliente['id'], $nombre, $estado);
    Response::ok(null, 'Cliente actualizado correctamente.');
}

public function destroy(): void {
    Auth::requireSeccion('clientes');
    Auth::requireRol(['ADMINISTRADOR']);
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $codigo = strtoupper(trim($body['codigo'] ?? ''));

    $cliente = $this->model->findByCodigo($codigo);
    if (!$cliente) Response::error('Cliente no encontrado.', 404);

    $eliminado = $this->model->delete($cliente['id']);
    if (!$eliminado) {
        Response::error('No se puede eliminar: el cliente tiene stock o movimientos registrados.');
    }
    Response::ok(null, 'Cliente eliminado correctamente.');
}
}
