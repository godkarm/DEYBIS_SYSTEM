<?php
class Auth {

    public static function requireLogin(): void {
        if (Session::has('usuario')) return;

        if (self::isApiRequest()) {
            Response::json(['ok' => false, 'mensaje' => 'Sesión inválida o expirada. Inicie sesión.'], 401);
            exit;
        }

        // BASE_URL garantiza el redirect correcto sin importar SCRIPT_NAME
        header('Location: ' . BASE_URL . '/');
        exit;
    }

    public static function requireRol(array $roles): void {
        if (!in_array(Session::rol(), $roles, true)) {
            Response::json(['ok' => false, 'mensaje' => 'Sin permisos para esta acción.'], 403);
            exit;
        }
    }

    public static function requireSeccion(string $slug): void {
        if (!Session::puedeAcceder($slug)) {
            Response::json(['ok' => false, 'mensaje' => 'Acceso denegado a esta sección.'], 403);
            exit;
        }
    }

    public static function resolverCliente(?string $solicitado): string {
        if (Session::rol() === 'CLIENTE') {
            return Session::clienteAsociado() ?? '';
        }
        return $solicitado ?? '';
    }

    private static function isApiRequest(): bool {
        $uri    = $_SERVER['REQUEST_URI'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT']  ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return str_contains($uri, '/api/')
            || str_contains($accept, 'application/json')
            || in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']);
    }
}