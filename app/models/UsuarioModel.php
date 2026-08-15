<?php
class UsuarioModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUsername(string $usuario): ?array {
        $stmt = $this->db->prepare("
            SELECT u.id, u.usuario, u.password_hash, u.estado,
                   r.nombre AS rol, r.id AS rol_id,
                   c.codigo AS cliente_codigo, c.nombre AS cliente_nombre
            FROM usuarios u
            JOIN roles r ON r.id = u.id_rol
            LEFT JOIN clientes c ON c.id = u.id_cliente
            WHERE u.usuario = ?
        ");
        $stmt->execute([$usuario]);
        return $stmt->fetch() ?: null;
    }

    /** Lista completa sin password_hash, para panel de admin */
    public function all(): array {
        $stmt = $this->db->query("
            SELECT u.id, u.usuario, r.nombre AS rol, u.estado,
                   c.codigo AS cliente_codigo, c.nombre AS cliente_nombre
            FROM usuarios u
            JOIN roles r ON r.id = u.id_rol
            LEFT JOIN clientes c ON c.id = u.id_cliente
            ORDER BY u.usuario
        ");
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (usuario, password_hash, id_rol, id_cliente, estado)
            VALUES (?, ?, ?, ?, 'ACTIVO')
        ");
        $stmt->execute([
            strtolower(trim($data['usuario'])),
            password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            $data['id_rol'],
            $data['id_cliente'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $sets   = [];
        $params = [];

        if (!empty($data['password'])) {
            $sets[]   = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        if (isset($data['id_rol'])) {
            $sets[]   = 'id_rol = ?';
            $params[] = $data['id_rol'];
        }
        if (array_key_exists('id_cliente', $data)) {
            $sets[]   = 'id_cliente = ?';
            $params[] = $data['id_cliente'];
        }
        if (isset($data['estado'])) {
            $sets[]   = 'estado = ?';
            $params[] = $data['estado'];
        }

        if (!$sets) return;

        $params[] = $id;
        $this->db->prepare(
            'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = ?'
        )->execute($params);
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    public function delete(int $id): void {
    $this->db->prepare("DELETE FROM usuarios WHERE id = ?")
             ->execute([$id]);
    }
}
