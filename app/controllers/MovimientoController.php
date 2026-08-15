<?php
class MovimientoController {
    private MovimientoModel $model;
    private ClienteModel    $clienteModel;

    public function __construct() {
        $this->model        = new MovimientoModel();
        $this->clienteModel = new ClienteModel();
    }

    public function store(): void {
        Auth::requireSeccion('movimientos');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $codCli = Auth::resolverCliente($body['cliente'] ?? null);
        if (!$codCli) Response::error('Debe seleccionar un cliente.');

        $cli = $this->clienteModel->findByCodigo($codCli);
        if (!$cli || $cli['estado'] !== 'ACTIVO') {
            Response::error('Cliente inactivo o no encontrado.');
        }

        $tipo     = strtoupper(trim($body['tipo'] ?? ''));
        $cantidad = (float)($body['cantidad'] ?? 0);

        if (!in_array($tipo, TIPOS_MOVIMIENTO, true)) {
            Response::error("Tipo de movimiento inválido: {$tipo}");
        }
        if ($cantidad <= 0) {
            Response::error('La cantidad debe ser mayor a 0.');
        }

        try {
            $resultado = $this->model->registrar([
                'id_cliente'   => $cli['id'],
                'codigo'       => strtoupper(trim($body['codigo'] ?? '')),
                'fecha'        => $body['fecha'] ?? date('Y-m-d'),
                'tipo'         => $tipo,
                'cantidad'     => $cantidad,
                'observaciones'=> $body['observaciones'] ?? null,
                'id_usuario'   => Session::idUsuario(),
            ]);

            if ($resultado !== 'OK') Response::error($resultado);
            Response::ok(null, 'Movimiento registrado correctamente.');
        } catch (Throwable $e) {
            Response::error('Error al registrar movimiento: ' . $e->getMessage(), 500);
        }
    }

    public function buscarProducto(): void {
        Auth::requireSeccion('movimientos');
        $texto  = trim($_GET['q'] ?? '');
        $codCli = Auth::resolverCliente($_GET['cliente'] ?? null);

        $idCliente = null;
        if ($codCli) {
            $cli       = (new ClienteModel())->findByCodigo($codCli);
            $idCliente = $cli['id'] ?? null;
        }

        Response::ok((new ProductoModel())->buscarPorNombre($texto, $idCliente));
    }
}
