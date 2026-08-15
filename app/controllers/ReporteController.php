<?php
class ReporteController {
    public function historial(): void {
        Auth::requireSeccion('reportes');
        $codCli    = Auth::resolverCliente($_GET['cliente'] ?? null);
        $idCliente = null;
        if ($codCli) {
            $cli       = (new ClienteModel())->findByCodigo($codCli);
            $idCliente = $cli['id'] ?? null;
        }

        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $rows = (new MovimientoModel())->historial([
            'fecha_desde' => $desde,
            'fecha_hasta' => $hasta,
            'id_cliente'  => $idCliente,
            'tipo'        => $_GET['tipo'] ?? null,
        ]);

        Response::ok($rows);
    }
}
