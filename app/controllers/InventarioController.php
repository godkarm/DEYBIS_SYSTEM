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
    public function actualizarStockMin(): void {
    Auth::requireSeccion('inventario');
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $codCli   = strtoupper(trim($body['cliente'] ?? ''));
    $codProd  = strtoupper(trim($body['codigo']  ?? ''));
    $stockMin = max(0, (float)($body['stock_min'] ?? 0));

    $cli  = $this->clienteModel->findByCodigo($codCli);
    if (!$cli) Response::error('Cliente no encontrado.', 404);

    $prod = (new ProductoModel())->findByCodigo($codProd);
    if (!$prod) Response::error('Producto no encontrado.', 404);

    $db   = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        UPDATE producto_cliente
        SET stock_min = ?
        WHERE id_cliente = ? AND id_producto = ?
    ");
    $stmt->execute([$stockMin, $cli['id'], $prod['id']]);

    if ($stmt->rowCount() === 0) {
        Response::error('Relación producto-cliente no encontrada.');
    }
    Response::ok(null, 'Stock mínimo actualizado correctamente.');
}
}
