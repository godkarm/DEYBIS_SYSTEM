<?php
class InventarioController {
    private InventarioModel $model;
    private ClienteModel    $clienteModel;

    public function __construct() {
        $this->model        = new InventarioModel();
        $this->clienteModel = new ClienteModel();
    }

    public function index(): void {
        Auth::requireSeccion('inventario');
        $codCli    = Auth::resolverCliente($_GET['cliente'] ?? null);
        $idCliente = null;
        if ($codCli) {
            $cli       = $this->clienteModel->findByCodigo($codCli);
            $idCliente = $cli['id'] ?? null;
        }
        Response::ok($this->model->stock($idCliente));
    }

    public function exportarCSV(): void {
        Auth::requireSeccion('inventario');
        $codCli    = Auth::resolverCliente($_GET['cliente'] ?? null);
        $idCliente = null;
        if ($codCli) {
            $cli       = $this->clienteModel->findByCodigo($codCli);
            $idCliente = $cli['id'] ?? null;
        }

        $rows = $this->model->stock($idCliente);
        if (!$rows) { Response::error('Sin datos para exportar.'); }

        $nombre = 'Inventario_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$nombre}\"");

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
        fputcsv($out, ['Cliente','Código','Nombre','Unidad','Grupo','Stock Mín','Stock Actual','Estado']);

        foreach ($rows as $r) {
            $estado = $r['stock_actual'] <= 0 ? 'Sin Stock'
                    : ($r['stock_min'] > 0 && $r['stock_actual'] <= $r['stock_min'] ? 'Stock Bajo' : 'Normal');
            fputcsv($out, [
                $r['cliente_nombre'], $r['codigo'], $r['nombre'],
                $r['unidad'], $r['grupo'], $r['stock_min'], $r['stock_actual'], $estado,
            ]);
        }
        fclose($out);
        exit;
    }
}
