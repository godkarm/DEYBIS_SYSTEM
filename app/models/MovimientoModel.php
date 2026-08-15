<?php
class MovimientoModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Registra un movimiento en transacción:
     * 1. Bloquea la fila de producto_cliente (FOR UPDATE)
     * 2. Valida stock disponible para salidas
     * 3. Actualiza el caché de stock
     * 4. Inserta en movimientos
     */
    public function registrar(array $data): string {
        $this->db->beginTransaction();
        try {
            // Obtener id_pc
            $stmt = $this->db->prepare("
                SELECT pc.id, pc.stock_actual, p.id AS id_producto
                FROM producto_cliente pc
                JOIN productos p ON p.id = pc.id_producto
                WHERE pc.id_cliente = ? AND p.codigo = ?
                FOR UPDATE
            ");
            $stmt->execute([$data['id_cliente'], strtoupper($data['codigo'])]);
            $pc = $stmt->fetch();

            if (!$pc) {
                $this->db->rollBack();
                return 'El producto no está habilitado para este cliente.';
            }

            $tipo     = strtoupper($data['tipo']);
            $cantidad = (float) $data['cantidad'];
            $stock    = (float) $pc['stock_actual'];

            $delta = match($tipo) {
                'INGRESO', 'AJUSTE_POSITIVO' =>  $cantidad,
                'SALIDA',  'AJUSTE_NEGATIVO' => -$cantidad,
                'AJUSTE'                     =>  $cantidad,
                default                      =>  0,
            };

            if (($tipo === 'SALIDA' || $tipo === 'AJUSTE_NEGATIVO') && $stock < $cantidad) {
                $this->db->rollBack();
                return "Stock insuficiente. Disponible: {$stock}, Solicitado: {$cantidad}";
            }

            $stockResultante = max(0, round($stock + $delta, 2));

            // Actualizar caché
            $this->db->prepare(
                "UPDATE producto_cliente SET stock_actual = ? WHERE id = ?"
            )->execute([$stockResultante, $pc['id']]);

            // Insertar movimiento
            $this->db->prepare("
                INSERT INTO movimientos
                  (id_pc, fecha_movimiento, tipo, cantidad, stock_resultante, observaciones, id_usuario)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $pc['id'],
                $data['fecha'],
                $tipo,
                $cantidad,
                $stockResultante,
                $data['observaciones'] ?? null,
                $data['id_usuario'],
            ]);

            $this->db->commit();
            return 'OK';
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function historial(array $filtros): array {
        $where  = ['m.fecha_movimiento BETWEEN :desde AND :hasta'];
        $params = [
            ':desde' => $filtros['fecha_desde'],
            ':hasta' => $filtros['fecha_hasta'],
        ];

        if (!empty($filtros['id_cliente'])) {
            $where[]            = 'pc.id_cliente = :id_cliente';
            $params[':id_cliente'] = $filtros['id_cliente'];
        }
        if (!empty($filtros['tipo'])) {
            $where[]         = 'm.tipo = :tipo';
            $params[':tipo'] = strtoupper($filtros['tipo']);
        }

        $sql = "
            SELECT m.id, m.fecha_movimiento, m.tipo, m.cantidad, m.stock_resultante,
                   m.observaciones, u.usuario AS registrado_por,
                   p.codigo, p.nombre AS producto,
                   c.codigo AS cliente_codigo, c.nombre AS cliente_nombre
            FROM movimientos m
            JOIN producto_cliente pc ON pc.id = m.id_pc
            JOIN productos p         ON p.id  = pc.id_producto
            JOIN clientes  c         ON c.id  = pc.id_cliente
            JOIN usuarios  u         ON u.id  = m.id_usuario
            WHERE " . implode(' AND ', $where) . "
            ORDER BY m.fecha_movimiento DESC, m.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Recalcula stock_actual de TODOS los pares desde el historial completo */
    public function recalcularCache(): int {
        $this->db->beginTransaction();
        try {
            // Reset a 0
            $this->db->exec("UPDATE producto_cliente SET stock_actual = 0");

            $stmt = $this->db->query("
                SELECT id_pc, tipo, SUM(cantidad) AS total
                FROM movimientos
                GROUP BY id_pc, tipo
            ");
            $filas = $stmt->fetchAll();

            $acum = [];
            foreach ($filas as $f) {
                $id    = $f['id_pc'];
                $delta = match($f['tipo']) {
                    'INGRESO', 'AJUSTE_POSITIVO' =>  (float)$f['total'],
                    'SALIDA',  'AJUSTE_NEGATIVO' => -(float)$f['total'],
                    'AJUSTE'                     =>  (float)$f['total'],
                    default                      =>  0,
                };
                $acum[$id] = ($acum[$id] ?? 0) + $delta;
            }

            $upd = $this->db->prepare(
                "UPDATE producto_cliente SET stock_actual = ? WHERE id = ?"
            );
            foreach ($acum as $id => $val) {
                $upd->execute([max(0, round($val, 2)), $id]);
            }

            $this->db->commit();
            return count($acum);
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
