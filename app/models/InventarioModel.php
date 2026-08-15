<?php
class InventarioModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function stock(?int $idCliente = null): array {
        $where  = $idCliente ? 'WHERE pc.id_cliente = ?' : '';
        $params = $idCliente ? [$idCliente] : [];

        $stmt = $this->db->prepare("
            SELECT c.codigo  AS cliente_codigo,
                   c.nombre  AS cliente_nombre,
                   p.codigo, p.nombre,
                   u.nombre  AS unidad,
                   g.nombre  AS grupo,
                   pc.stock_min, pc.stock_actual
            FROM producto_cliente pc
            JOIN clientes  c ON c.id = pc.id_cliente
            JOIN productos p ON p.id = pc.id_producto
            JOIN unidades  u ON u.id = p.id_unidad
            JOIN grupos    g ON g.id = p.id_grupo
            $where
            ORDER BY p.nombre
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function resumen(?int $idCliente = null): array {
        $rows  = $this->stock($idCliente);
        $total = count($rows);
        $sinStock = $bajo = 0;
        foreach ($rows as $r) {
            if ($r['stock_actual'] <= 0) $sinStock++;
            elseif ($r['stock_min'] > 0 && $r['stock_actual'] <= $r['stock_min']) $bajo++;
        }

        // Movimientos del último mes
        $where  = $idCliente ? 'AND pc.id_cliente = ?' : '';
        $params = $idCliente ? [$idCliente] : [];
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN m.fecha_movimiento >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS ultimo_mes
            FROM movimientos m
            JOIN producto_cliente pc ON pc.id = m.id_pc
            WHERE 1=1 $where
        ");
        $stmt->execute($params);
        $mov = $stmt->fetch();

        return [
            'totalProductos'         => $total,
            'sinStock'               => $sinStock,
            'stockBajo'              => $bajo,
            'totalMovimientos'       => (int)($mov['total'] ?? 0),
            'movimientosUltimoMes'   => (int)($mov['ultimo_mes'] ?? 0),
        ];
    }

    public function alertas(?int $idCliente = null): array {
        return array_filter(
            $this->stock($idCliente),
            fn($r) => $r['stock_actual'] <= 0 ||
                      ($r['stock_min'] > 0 && $r['stock_actual'] <= $r['stock_min'])
        );
    }
}
