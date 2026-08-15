<?php
class DashboardController {
    private InventarioModel $model;
    private ClienteModel    $clienteModel;

    public function __construct() {
        $this->model        = new InventarioModel();
        $this->clienteModel = new ClienteModel();
    }

    private function idCliente(): ?int {
        $cod = Auth::resolverCliente($_GET['cliente'] ?? null);
        if (!$cod) return null;
        $cli = $this->clienteModel->findByCodigo($cod);
        return $cli ? (int)$cli['id'] : null;
    }

    public function resumen(): void {
        Auth::requireSeccion('dashboard');
        Response::ok($this->model->resumen($this->idCliente()));
    }

    public function alertas(): void {
        Auth::requireSeccion('dashboard');
        Response::ok(array_values($this->model->alertas($this->idCliente())));
    }
}
