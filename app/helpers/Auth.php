<?php
/**
 * Auth.php — Guards de autenticación
 */
class Auth {

    /**
     * Redirige al login si no hay sesión activa.
     * Usa el mismo scriptBase que index.php para el redirect.
     */
    public static function requireLogin(): void {
        if (Session::has('usuario')) return;

        if (self::isApiRequest()) {
            Response::json(['ok' => false, 'mensaje' => 'Sesión inválida o expirada. Inicie sesión.'], 401);
            exit;
        }

        // Redirigir al login usando la ruta detectada automáticamente
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        header('Location: ' . $base . '/');
        exit;
    }

    /** Lanza 403 si el rol no está en la lista permitida */
    public static function requireRol(array $roles): void {
        if (!in_array(Session::rol(), $roles, true)) {
            Response::json(['ok' => false, 'mensaje' => 'Sin permisos para esta acción.'], 403);
            exit;
        }
    }

    /** Lanza 403 si la sección no está habilitada */
    public static function requireSeccion(string $slug): void {
        if (!Session::puedeAcceder($slug)) {
            Response::json(['ok' => false, 'mensaje' => 'Acceso denegado a esta sección.'], 403);
            exit;
        }
    }

    /** Para rol CLIENTE fuerza siempre su cliente_asociado */
    public static function resolverCliente(?string $solicitado): string {
        if (Session::rol() === 'CLIENTE') {
            return Session::clienteAsociado() ?? '';
        }
        return $solicitado ?? '';
    }

    /** ¿Es una petición a la API? */
    private static function isApiRequest(): bool {
        $uri    = $_SERVER['REQUEST_URI'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT']  ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return str_contains($uri, '/api/')
            || str_contains($accept, 'application/json')
            || in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']);
    }
}
