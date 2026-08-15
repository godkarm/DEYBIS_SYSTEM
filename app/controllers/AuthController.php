<?php
/**
 * AuthController — Login / Logout
 */
class AuthController {

    /** GET / → pantalla de login */
    public function showLogin(): void {
        // Si YA hay sesión → redirigir al app sin loop
        if (Session::has('usuario')) {
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            header('Location: ' . $base . '/app');
            exit;
        }
        Response::view('auth/login');
    }

    /** POST /api/login */
    public function login(): void {
        $body     = json_decode(file_get_contents('php://input'), true) ?? [];
        $usuario  = strtolower(trim($body['usuario']  ?? ''));
        $password =            $body['password'] ?? '';

        if (!$usuario || !$password) {
            Response::error('Usuario y contraseña son obligatorios.');
        }

        $model  = new UsuarioModel();
        $cuenta = $model->findByUsername($usuario);

        if (!$cuenta || !password_verify($password, $cuenta['password_hash'])) {
            Response::error('Usuario o contraseña incorrectos.');
        }

        if ($cuenta['estado'] !== 'ACTIVO') {
            Response::error('Este usuario está inactivo. Contacte al administrador.');
        }

        if ($cuenta['rol'] === 'CLIENTE') {
            $cli = (new ClienteModel())->findByCodigo($cuenta['cliente_codigo'] ?? '');
            if (!$cli) {
                Response::error('El usuario no tiene un cliente asociado válido.');
            }
            if ($cli['estado'] !== 'ACTIVO') {
                Response::error('El cliente asociado está inactivo.');
            }
        }

        $permisos = (new PermisoModel())->resolverPermisos(
            $cuenta['id'], $cuenta['rol_id']
        );

        // Regenerar ID de sesión (seguridad: evita session fixation)
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id'             => $cuenta['id'],
            'usuario'        => $cuenta['usuario'],
            'rol'            => $cuenta['rol'],
            'rol_id'         => $cuenta['rol_id'],
            'cliente_codigo' => $cuenta['cliente_codigo'] ?? null,
            'cliente_nombre' => $cuenta['cliente_nombre'] ?? null,
        ];
        $_SESSION['permisos'] = $permisos;

        // Calcular redirect usando el base detectado automáticamente
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

        Response::ok([
            'usuario'        => $cuenta['usuario'],
            'rol'            => $cuenta['rol'],
            'clienteAsociado'=> $cuenta['cliente_codigo'] ?? '',
            'clienteNombre'  => $cuenta['cliente_nombre'] ?? '',
            'permisos'       => $permisos,
            'redirect'       => $base . '/app',
        ], 'Sesión iniciada.');
    }

    /** POST /api/logout */
    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $p['path'], $p['domain'],
                $p['secure'], $p['httponly']
            );
        }
        session_destroy();
        Response::ok(null, 'Sesión cerrada.');
    }
}
