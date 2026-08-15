<?php
class UsuarioController {
    private UsuarioModel $model;
    private PermisoModel $permisoModel;
    private ClienteModel $clienteModel;

    public function __construct() {
        $this->model        = new UsuarioModel();
        $this->permisoModel = new PermisoModel();
        $this->clienteModel = new ClienteModel();
    }

    public function index(): void {
        Auth::requireSeccion('usuarios');
        Auth::requireRol(['ADMINISTRADOR']);
        Response::ok($this->model->all());
    }

    public function store(): void {
        Auth::requireSeccion('usuarios');
        Auth::requireRol(['ADMINISTRADOR']);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $usuario  = strtolower(trim($body['usuario'] ?? ''));
        $password = $body['password'] ?? '';
        $rol      = strtoupper(trim($body['rol'] ?? ''));

        if (strlen($usuario) < 3) Response::error('El usuario debe tener al menos 3 caracteres.');
        if (strlen($password) < 4) Response::error('La contraseña debe tener al menos 4 caracteres.');

        $roles = ['ADMINISTRADOR' => 1, 'ALMACENERO' => 2, 'CLIENTE' => 3];
        if (!isset($roles[$rol])) Response::error("Rol inválido: {$rol}");

        $idCliente = null;
        if ($rol === 'CLIENTE') {
            $cli = $this->clienteModel->findByCodigo($body['cliente'] ?? '');
            if (!$cli) Response::error('El cliente asociado no existe.');
            $idCliente = $cli['id'];
        }

        $this->model->create([
            'usuario'    => $usuario,
            'password'   => $password,
            'id_rol'     => $roles[$rol],
            'id_cliente' => $idCliente,
        ]);

        Response::ok(null, 'Usuario creado correctamente.');
    }

    public function update(): void {
        Auth::requireSeccion('usuarios');
        Auth::requireRol(['ADMINISTRADOR']);
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $usuario = strtolower(trim($body['usuario'] ?? ''));

        $cuenta = $this->model->findByUsername($usuario);
        if (!$cuenta) Response::error('Usuario no encontrado.', 404);

        $cambios = [];
        if (!empty($body['password'])) $cambios['password'] = $body['password'];
        if (!empty($body['estado']))   $cambios['estado']   = strtoupper($body['estado']);

        $this->model->update($cuenta['id'], $cambios);
        Response::ok(null, 'Usuario actualizado.');
    }

    public function permisos(): void {
        Auth::requireSeccion('usuarios');
        Auth::requireRol(['ADMINISTRADOR']);
        Response::ok([
            'secciones'  => $this->permisoModel->setSecciones(),
            'porRol'     => $this->permisoModel->matrizRoles(),
            'porUsuario' => $this->permisoModel->overridesUsuarios(),
        ]);
    }

    public function setPermiso(): void {
        Auth::requireSeccion('usuarios');
        Auth::requireRol(['ADMINISTRADOR']);
        $body     = json_decode(file_get_contents('php://input'), true) ?? [];
        $tipo     = strtoupper($body['tipo'] ?? '');           // ROL | USUARIO
        $clave    = $body['clave'] ?? '';
        $slug     = $body['seccion'] ?? '';
        $permitido = (bool)($body['permitido'] ?? false);

        $seccion = $this->permisoModel->findSeccionBySlug($slug);
        if (!$seccion) Response::error("Sección inválida: {$slug}");

        if ($tipo === 'ROL') {
            $roles = ['ADMINISTRADOR' => 1, 'ALMACENERO' => 2, 'CLIENTE' => 3];
            if (!isset($roles[$clave])) Response::error("Rol inválido: {$clave}");
            $this->permisoModel->upsertRol($roles[$clave], $seccion['id'], $permitido);
        } elseif ($tipo === 'USUARIO') {
            $cuenta = $this->model->findByUsername(strtolower($clave));
            if (!$cuenta) Response::error("Usuario no encontrado: {$clave}");
            $this->permisoModel->upsertUsuario($cuenta['id'], $seccion['id'], $permitido);
        } else {
            Response::error('Tipo inválido. Use ROL o USUARIO.');
        }

        Response::ok(null, 'Permiso actualizado.');
    }

    public function quitarOverride(): void {
        Auth::requireSeccion('usuarios');
        Auth::requireRol(['ADMINISTRADOR']);
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $usuario = strtolower(trim($body['usuario'] ?? ''));
        $slug    = $body['seccion'] ?? '';

        $cuenta  = $this->model->findByUsername($usuario);
        $seccion = $this->permisoModel->findSeccionBySlug($slug);
        if (!$cuenta || !$seccion) Response::error('Usuario o sección no válidos.');

        $this->permisoModel->deleteOverride($cuenta['id'], $seccion['id']);
        Response::ok(null, 'Override eliminado. El usuario hereda el permiso de su rol.');
    }
}
