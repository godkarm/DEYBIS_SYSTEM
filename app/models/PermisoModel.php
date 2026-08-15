<?php
class PermisoModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Permisos efectivos: rol base + overrides de usuario.
     * Devuelve ['dashboard' => true, 'clientes' => false, ...]
     */
    public function resolverPermisos(int $idUsuario, int $idRol): array {
        // Permisos de rol
        $stmt = $this->db->prepare("
            SELECT s.slug, pr.permitido
            FROM permisos_rol pr
            JOIN secciones s ON s.id = pr.id_seccion
            WHERE pr.id_rol = ?
        ");
        $stmt->execute([$idRol]);
        $permisos = [];
        foreach ($stmt->fetchAll() as $row) {
            $permisos[$row['slug']] = (bool) $row['permitido'];
        }

        // Overrides individuales (segunda pasada — ganan siempre)
        $stmt = $this->db->prepare("
            SELECT s.slug, pu.permitido
            FROM permisos_usuario pu
            JOIN secciones s ON s.id = pu.id_seccion
            WHERE pu.id_usuario = ?
        ");
        $stmt->execute([$idUsuario]);
        foreach ($stmt->fetchAll() as $row) {
            $permisos[$row['slug']] = (bool) $row['permitido'];
        }

        return $permisos;
    }

    /** Matriz completa de permisos por rol (para el panel de admin) */
    public function matrizRoles(): array {
        $stmt = $this->db->query("
            SELECT r.nombre AS rol, s.slug, pr.permitido
            FROM permisos_rol pr
            JOIN roles    r ON r.id = pr.id_rol
            JOIN secciones s ON s.id = pr.id_seccion
        ");
        $matriz = [];
        foreach ($stmt->fetchAll() as $row) {
            $matriz[$row['rol']][$row['slug']] = (bool) $row['permitido'];
        }
        return $matriz;
    }

    /** Overrides individuales (para el panel de admin) */
    public function overridesUsuarios(): array {
        $stmt = $this->db->query("
            SELECT u.usuario, s.slug, pu.permitido
            FROM permisos_usuario pu
            JOIN usuarios  u ON u.id = pu.id_usuario
            JOIN secciones s ON s.id = pu.id_seccion
        ");
        $overrides = [];
        foreach ($stmt->fetchAll() as $row) {
            $overrides[$row['usuario']][$row['slug']] = (bool) $row['permitido'];
        }
        return $overrides;
    }

    public function setSecciones(): array {
        $stmt = $this->db->query("SELECT slug, etiqueta FROM secciones ORDER BY id");
        return $stmt->fetchAll();
    }

    /** Crea o actualiza un permiso de rol */
    public function upsertRol(int $idRol, int $idSeccion, bool $permitido): void {
        $this->db->prepare("
            INSERT INTO permisos_rol (id_rol, id_seccion, permitido)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE permitido = VALUES(permitido)
        ")->execute([$idRol, $idSeccion, (int)$permitido]);
    }

    /** Crea o actualiza un permiso individual de usuario */
    public function upsertUsuario(int $idUsuario, int $idSeccion, bool $permitido): void {
        $this->db->prepare("
            INSERT INTO permisos_usuario (id_usuario, id_seccion, permitido)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE permitido = VALUES(permitido)
        ")->execute([$idUsuario, $idSeccion, (int)$permitido]);
    }

    /** Elimina el override de un usuario para una sección (hereda el del rol) */
    public function deleteOverride(int $idUsuario, int $idSeccion): void {
        $this->db->prepare("
            DELETE FROM permisos_usuario WHERE id_usuario = ? AND id_seccion = ?
        ")->execute([$idUsuario, $idSeccion]);
    }

    public function findSeccionBySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM secciones WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }
}
