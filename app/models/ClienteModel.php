<?php
class ClienteModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(bool $soloActivos = false): array {
        $sql = "SELECT id, codigo, nombre, estado, created_at FROM clientes";
        if ($soloActivos) $sql .= " WHERE estado = 'ACTIVO'";
        $sql .= " ORDER BY nombre";
        return $this->db->query($sql)->fetchAll();
    }

    public function findByCodigo(string $codigo): ?array {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE codigo = ?");
        $stmt->execute([strtoupper(trim($codigo))]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $codigo, string $nombre): int {
        $stmt = $this->db->prepare(
            "INSERT INTO clientes (codigo, nombre, estado) VALUES (?, ?, 'ACTIVO')"
        );
        $stmt->execute([strtoupper(trim($codigo)), trim($nombre)]);
        return (int) $this->db->lastInsertId();
    }

    public function cambiarEstado(int $id, string $estado): void {
        $this->db->prepare("UPDATE clientes SET estado = ? WHERE id = ?")
                 ->execute([$estado, $id]);
    }

    /** Stock total del cliente en todos sus productos */
    public function stockTotal(int $idCliente): float {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(stock_actual), 0) FROM producto_cliente WHERE id_cliente = ?"
        );
        $stmt->execute([$idCliente]);
        return (float) $stmt->fetchColumn();
    }
    public function actualizar(int $id, string $nombre, string $estado): void {
    $this->db->prepare(
        "UPDATE clientes SET nombre = ?, estado = ? WHERE id = ?"
    )->execute([trim($nombre), $estado, $id]);
}

public function delete(int $id): bool {
    // Verificar que no tenga stock ni movimientos
    $stmt = $this->db->prepare("
        SELECT COUNT(*) FROM producto_cliente pc
        JOIN movimientos m ON m.id_pc = pc.id
        WHERE pc.id_cliente = ?
    ");
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) return false;

    $stmt = $this->db->prepare("
        SELECT COALESCE(SUM(stock_actual), 0) FROM producto_cliente WHERE id_cliente = ?
    ");
    $stmt->execute([$id]);
    if ((float)$stmt->fetchColumn() > 0) return false;

    $this->db->prepare("DELETE FROM producto_cliente WHERE id_cliente = ?")->execute([$id]);
    $this->db->prepare("DELETE FROM clientes WHERE id = ?")->execute([$id]);
    return true;
}
}
