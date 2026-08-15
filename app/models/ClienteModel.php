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
}
