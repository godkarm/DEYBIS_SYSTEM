<?php
class RequerimientoModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function generarNumero(): string {
        $stmt = $this->db->prepare(
            "SELECT ultimo_num FROM correlativos WHERE prefijo = 'REQ' FOR UPDATE"
        );
        $stmt->execute();
        $row  = $stmt->fetch();
        $sig  = ($row ? (int)$row['ultimo_num'] : 0) + 1;
        $this->db->prepare(
            "UPDATE correlativos SET ultimo_num = ? WHERE prefijo = 'REQ'"
        )->execute([$sig]);
        return 'REQ-' . str_pad($sig, 4, '0', STR_PAD_LEFT);
    }

    public function crear(int $idCliente, int $idUsuario, string $obs, array $items): int {
        $this->db->beginTransaction();
        try {
            $numero = $this->generarNumero();
            $this->db->prepare("
                INSERT INTO requerimientos (numero, id_cliente, id_usuario_solicitante, observaciones)
                VALUES (?, ?, ?, ?)
            ")->execute([$numero, $idCliente, $idUsuario, $obs ?: null]);

            $idReq = (int)$this->db->lastInsertId();

            $ins = $this->db->prepare("
                INSERT INTO requerimiento_items (id_requerimiento, id_pc, cantidad_solicitada, observaciones)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($items as $item) {
                $ins->execute([
                    $idReq,
                    $item['id_pc'],
                    (float)$item['cantidad'],
                    $item['obs'] ?? null,
                ]);
            }

            $this->db->commit();
            return $idReq;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function all(?int $idCliente = null, ?string $estado = null): array {
        $where  = ['1=1'];
        $params = [];

        if ($idCliente) {
            $where[]  = 'r.id_cliente = ?';
            $params[] = $idCliente;
        }
        if ($estado) {
            $where[]  = 'r.estado = ?';
            $params[] = $estado;
        }

        $sql = "
            SELECT r.id, r.numero, r.estado, r.observaciones,
                   r.created_at, r.updated_at,
                   c.nombre AS cliente_nombre, c.codigo AS cliente_codigo,
                   u.usuario AS solicitado_por,
                   COUNT(ri.id) AS total_items
            FROM requerimientos r
            JOIN clientes  c ON c.id = r.id_cliente
            JOIN usuarios  u ON u.id = r.id_usuario_solicitante
            LEFT JOIN requerimiento_items ri ON ri.id_requerimiento = r.id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY r.id
            ORDER BY r.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT r.*, c.nombre AS cliente_nombre, c.codigo AS cliente_codigo,
                   u.usuario AS solicitado_por
            FROM requerimientos r
            JOIN clientes c ON c.id = r.id_cliente
            JOIN usuarios u ON u.id = r.id_usuario_solicitante
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function items(int $idReq): array {
        $stmt = $this->db->prepare("
            SELECT ri.id, ri.cantidad_solicitada, ri.cantidad_despachada, ri.observaciones,
                   p.codigo, p.nombre AS producto, u.nombre AS unidad,
                   pc.stock_actual, pc.id AS id_pc
            FROM requerimiento_items ri
            JOIN producto_cliente pc ON pc.id = ri.id_pc
            JOIN productos p         ON p.id  = pc.id_producto
            JOIN unidades  u         ON u.id  = p.id_unidad
            WHERE ri.id_requerimiento = ?
        ");
        $stmt->execute([$idReq]);
        return $stmt->fetchAll();
    }

    public function cambiarEstado(int $id, string $estado): void {
        $this->db->prepare("
            UPDATE requerimientos SET estado = ? WHERE id = ?
        ")->execute([$estado, $id]);
    }

    public function despachar(int $idReq, array $despachos, int $idUsuario): void {
        $this->db->beginTransaction();
        try {
            $req = $this->findById($idReq);
            if (!$req) throw new \Exception('Requerimiento no encontrado.');

            foreach ($despachos as $d) {
                $idItem  = (int)$d['id_item'];
                $cantDes = (float)$d['cantidad_despachada'];
                if ($cantDes <= 0) continue;

                // Obtener item
                $stmt = $this->db->prepare("
                    SELECT ri.*, pc.stock_actual, pc.id_cliente, p.codigo
                    FROM requerimiento_items ri
                    JOIN producto_cliente pc ON pc.id = ri.id_pc
                    JOIN productos p ON p.id = pc.id_producto
                    WHERE ri.id = ? AND ri.id_requerimiento = ?
                ");
                $stmt->execute([$idItem, $idReq]);
                $item = $stmt->fetch();
                if (!$item) continue;

                if ($cantDes > $item['stock_actual']) {
                    throw new \Exception(
                        "Stock insuficiente para {$item['codigo']}: " .
                        "disponible={$item['stock_actual']}, solicitado={$cantDes}"
                    );
                }

                // Descontar stock
                $nuevoStock = round($item['stock_actual'] - $cantDes, 2);
                $this->db->prepare(
                    "UPDATE producto_cliente SET stock_actual = ? WHERE id = ?"
                )->execute([$nuevoStock, $item['id_pc']]);

                // Registrar movimiento SALIDA
                $this->db->prepare("
                    INSERT INTO movimientos
                      (id_pc, fecha_movimiento, tipo, cantidad, stock_resultante, observaciones, id_usuario)
                    VALUES (?, CURDATE(), 'SALIDA', ?, ?, ?, ?)
                ")->execute([
                    $item['id_pc'],
                    $cantDes,
                    $nuevoStock,
                    "Despacho {$req['numero']}",
                    $idUsuario,
                ]);

                // Actualizar cantidad despachada en el ítem
                $this->db->prepare("
                    UPDATE requerimiento_items SET cantidad_despachada = ? WHERE id = ?
                ")->execute([$cantDes, $idItem]);
            }

            // Cambiar estado a DESPACHADO
            $this->cambiarEstado($idReq, 'DESPACHADO');
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}