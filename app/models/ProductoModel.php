<?php
class ProductoModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function catalogo(): array {
        $stmt = $this->db->query("
            SELECT p.id, p.codigo, p.nombre,
                   u.nombre AS unidad, g.nombre AS grupo, g.prefijo
            FROM productos p
            JOIN unidades u ON u.id = p.id_unidad
            JOIN grupos   g ON g.id = p.id_grupo
            ORDER BY p.nombre
        ");
        return $stmt->fetchAll();
    }

    public function findByCodigo(string $codigo): ?array {
        $stmt = $this->db->prepare("SELECT * FROM productos WHERE codigo = ?");
        $stmt->execute([strtoupper(trim($codigo))]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Genera el siguiente código para un grupo (PREFIJO + 4 dígitos)
     * y lo reserva en la tabla correlativos.
     * DEBE llamarse dentro de una transacción con SELECT … FOR UPDATE.
     */
    public function generarCodigo(string $prefijo): string {
        $stmt = $this->db->prepare(
            "SELECT ultimo_num FROM correlativos WHERE prefijo = ? FOR UPDATE"
        );
        $stmt->execute([$prefijo]);
        $row = $stmt->fetch();
        $siguiente = ($row ? (int)$row['ultimo_num'] : 0) + 1;

        $this->db->prepare(
            "INSERT INTO correlativos (prefijo, ultimo_num) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE ultimo_num = VALUES(ultimo_num)"
        )->execute([$prefijo, $siguiente]);

        $num = $siguiente < 10000
            ? str_pad($siguiente, 4, '0', STR_PAD_LEFT)
            : (string)$siguiente;

        return $prefijo . $num;
    }

    public function create(string $codigo, string $nombre, int $idUnidad, int $idGrupo): int {
        $stmt = $this->db->prepare(
            "INSERT INTO productos (codigo, nombre, id_unidad, id_grupo) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$codigo, trim($nombre), $idUnidad, $idGrupo]);
        return (int) $this->db->lastInsertId();
    }

    public function buscarPorNombre(string $texto, ?int $idCliente = null): array {
        if ($idCliente) {
            $stmt = $this->db->prepare("
                SELECT p.codigo, p.nombre, u.nombre AS unidad, g.nombre AS grupo
                FROM productos p
                JOIN unidades u ON u.id = p.id_unidad
                JOIN grupos   g ON g.id = p.id_grupo
                JOIN producto_cliente pc ON pc.id_producto = p.id AND pc.id_cliente = ?
                WHERE p.nombre LIKE ?
                LIMIT 10
            ");
            $stmt->execute([$idCliente, "%{$texto}%"]);
        } else {
            $stmt = $this->db->prepare("
                SELECT p.codigo, p.nombre, u.nombre AS unidad, g.nombre AS grupo
                FROM productos p
                JOIN unidades u ON u.id = p.id_unidad
                JOIN grupos   g ON g.id = p.id_grupo
                WHERE p.nombre LIKE ?
                LIMIT 10
            ");
            $stmt->execute(["%{$texto}%"]);
        }
        return $stmt->fetchAll();
    }

    public function listas(): array {
        $u = $this->db->query("SELECT nombre FROM unidades ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);
        $g = $this->db->query("SELECT nombre FROM grupos   ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);
        return ['unidades' => $u, 'grupos' => $g];
    }

    // ── Producto-Cliente ──────────────────────────────────────

    public function habilitar(int $idCliente, int $idProducto, float $stockMin): void {
        $this->db->prepare("
            INSERT INTO producto_cliente (id_cliente, id_producto, stock_min, stock_actual)
            VALUES (?, ?, ?, 0)
        ")->execute([$idCliente, $idProducto, max(0, $stockMin)]);
    }

    public function deshabilitar(int $idCliente, int $idProducto): void {
        $this->db->prepare(
            "DELETE FROM producto_cliente WHERE id_cliente = ? AND id_producto = ? AND stock_actual = 0"
        )->execute([$idCliente, $idProducto]);
    }

    public function stockPC(int $idCliente, int $idProducto): float {
        $stmt = $this->db->prepare(
            "SELECT stock_actual FROM producto_cliente WHERE id_cliente = ? AND id_producto = ?"
        );
        $stmt->execute([$idCliente, $idProducto]);
        return (float)($stmt->fetchColumn() ?? 0);
    }

    public function findPC(int $idCliente, int $idProducto): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM producto_cliente WHERE id_cliente = ? AND id_producto = ?"
        );
        $stmt->execute([$idCliente, $idProducto]);
        return $stmt->fetch() ?: null;
    }
    public function actualizar(int $id, string $nombre, string $unidad): void {
    $db = $this->db;
    $idUnidad = (int)$db->query(
        "SELECT id FROM unidades WHERE nombre = " . $db->quote($unidad)
    )->fetchColumn();

    $db->prepare("UPDATE productos SET nombre = ?, id_unidad = ? WHERE id = ?")
       ->execute([trim($nombre), $idUnidad, $id]);
}

public function delete(int $id): bool {
    // Verificar movimientos
    $stmt = $this->db->prepare("
        SELECT COUNT(*) FROM movimientos m
        JOIN producto_cliente pc ON pc.id = m.id_pc
        WHERE pc.id_producto = ?
    ");
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) return false;

    // Verificar stock
    $stmt = $this->db->prepare("
        SELECT COALESCE(SUM(stock_actual), 0)
        FROM producto_cliente WHERE id_producto = ?
    ");
    $stmt->execute([$id]);
    if ((float)$stmt->fetchColumn() > 0) return false;

    $this->db->prepare("DELETE FROM producto_cliente WHERE id_producto = ?")->execute([$id]);
    $this->db->prepare("DELETE FROM productos WHERE id = ?")->execute([$id]);
    return true;
}
}
