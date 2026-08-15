<?php
class BuscarController {
    public function search(): void {
        Auth::requireSeccion('buscar');
        $texto  = trim($_GET['q'] ?? '');
        $codCli = Auth::resolverCliente($_GET['cliente'] ?? null);

        $idCliente = null;
        if ($codCli) {
            $cli       = (new ClienteModel())->findByCodigo($codCli);
            $idCliente = $cli['id'] ?? null;
        }

        $rows = (new InventarioModel())->stock($idCliente);

        if ($texto) {
            $t    = strtolower($texto);
            $rows = array_filter($rows, function ($r) use ($t) {
                return str_contains(strtolower($r['codigo']), $t)
                    || str_contains(strtolower($r['nombre']), $t)
                    || str_contains(strtolower($r['grupo']), $t)
                    || str_contains(strtolower($r['cliente_nombre']), $t);
            });
        }

        Response::ok(array_values($rows));
    }
}
