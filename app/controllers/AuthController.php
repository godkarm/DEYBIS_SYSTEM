<?php
class AuthController {

    public function showLogin(): void {
        if (Session::has('usuario')) {
            header('Location: ' . BASE_URL . '/app');
            exit;
        }
        Response::view('auth/login');
    }

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

        // Regenerar ID con los params de cookie correctos (BASE_URL garantiza consistencia)
        $cookiePath = rtrim(BASE_URL, '/') . '/';
        
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

        Response::ok([
            'usuario'        => $cuenta['usuario'],
            'rol'            => $cuenta['rol'],
            'clienteAsociado'=> $cuenta['cliente_codigo'] ?? '',
            'clienteNombre'  => $cuenta['cliente_nombre'] ?? '',
            'permisos'       => $permisos,
            'redirect'       => BASE_URL . '/app',
        ], 'Sesión iniciada.');
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $cookiePath = rtrim(BASE_URL, '/') . '/';
            setcookie(session_name(), '', time() - 42000, $cookiePath);
        }
        session_destroy();
        Response::ok(null, 'Sesión cerrada.');
    }
}