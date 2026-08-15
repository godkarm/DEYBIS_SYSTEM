<?php
/**
 * Gestión de sesión PHP + token de sesión en BD
 */
class Session {
    public static function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public static function destroy(): void {
        session_unset();
        session_destroy();
    }

    /** Datos del usuario logueado (shortcut) */
    public static function usuario(): ?array {
        return $_SESSION['usuario'] ?? null;
    }

    public static function idUsuario(): ?int {
        return $_SESSION['usuario']['id'] ?? null;
    }

    public static function rol(): ?string {
        return $_SESSION['usuario']['rol'] ?? null;
    }

    public static function clienteAsociado(): ?string {
        return $_SESSION['usuario']['cliente_codigo'] ?? null;
    }

    /**
     * Permisos efectivos cacheados en sesión.
     * Formato: ['dashboard' => true, 'clientes' => false, ...]
     */
    public static function permisos(): array {
        return $_SESSION['permisos'] ?? [];
    }

    public static function puedeAcceder(string $seccionSlug): bool {
        return !empty($_SESSION['permisos'][$seccionSlug]);
    }
}
