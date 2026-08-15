<?php
/**
 * public/index.php — Front Controller DEYBIS SYSTEM
 * Fix: rutas con UN solo espacio entre método y URI
 */

define('ROOT', dirname(__DIR__));
require ROOT . '/config/app.php';

// ── 1. Session save path ──────────────────────────────────────
$candidates = [
    'C:/xampp/tmp',
    'C:/XAMPP/tmp',
    'D:/xampp/tmp',
    'D:/XAMPP/tmp',
    sys_get_temp_dir(),
    ROOT . '/storage/sessions',
];
$sessionSavePath = '';
foreach ($candidates as $c) {
    if (is_dir($c) && is_writable($c)) {
        $sessionSavePath = $c;
        break;
    }
}
if (!$sessionSavePath) {
    $fallback = ROOT . '/storage/sessions';
    if (!is_dir($fallback)) @mkdir($fallback, 0755, true);
    $sessionSavePath = $fallback;
}
session_save_path($sessionSavePath);

// ── 2. Cookie path usando BASE_URL (consistente en todos los requests) ──
$sessionCookiePath = rtrim(BASE_URL, '/') . '/';

session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => $sessionCookiePath,
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// ── 3. Dependencias ───────────────────────────────────────────
require ROOT . '/config/database.php';
require ROOT . '/app/helpers/Session.php';
require ROOT . '/app/helpers/Auth.php';
require ROOT . '/app/helpers/Response.php';

spl_autoload_register(function (string $class): void {
    foreach ([
        ROOT . '/app/models/'      . $class . '.php',
        ROOT . '/app/controllers/' . $class . '.php',
    ] as $path) {
        if (file_exists($path)) { require $path; return; }
    }
});

// ── 4. URI ────────────────────────────────────────────────────
$rawUri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptBase = rtrim(BASE_URL, '/');

if ($scriptBase !== '' && str_starts_with($rawUri, $scriptBase)) {
    $uri = substr($rawUri, strlen($scriptBase));
} else {
    $uri = $rawUri;
}
$uri = '/' . ltrim($uri, '/');
if ($uri !== '/' && str_ends_with($uri, '/')) {
    $uri = rtrim($uri, '/');
}

$method = $_SERVER['REQUEST_METHOD'];
$key    = "$method $uri";   // UN solo espacio — igual que las claves del array

// ── 5. Rutas públicas (UN espacio entre método y ruta) ────────
$publicRoutes = [
    'GET /'           => ['AuthController', 'showLogin'],
    'POST /api/login'  => ['AuthController', 'login'],
    'POST /api/logout' => ['AuthController', 'logout'],
];

if (isset($publicRoutes[$key])) {
    [$ctrl, $action] = $publicRoutes[$key];
    (new $ctrl())->$action();
    exit;
}

// ── 6. Rutas protegidas (UN espacio entre método y ruta) ──────
Auth::requireLogin();

$routes = [
    'GET /api/dashboard/resumen'           => ['DashboardController',    'resumen'],
    'GET /api/dashboard/alertas'           => ['DashboardController',    'alertas'],
    'GET /api/clientes'                    => ['ClienteController',      'index'],
    'POST /api/clientes'                   => ['ClienteController',      'store'],
    'POST /api/clientes/estado'            => ['ClienteController',      'cambiarEstado'],
    'GET /api/productos/catalogo'          => ['ProductoController',     'catalogo'],
    'POST /api/productos'                  => ['ProductoController',     'store'],
    'POST /api/productos/habilitar'        => ['ProductoController',     'habilitar'],
    'POST /api/productos/deshabilitar'     => ['ProductoController',     'deshabilitar'],
    'GET /api/productos/buscar'            => ['ProductoController',     'buscar'],
    'GET /api/productos/listas'            => ['ProductoController',     'listas'],
    'GET /api/productos/prefijos'          => ['ProductoController',     'prefijos'],
    'POST /api/movimientos'                => ['MovimientoController',   'store'],
    'GET /api/movimientos/buscar-producto' => ['MovimientoController',   'buscarProducto'],
    'GET /api/inventario'                  => ['InventarioController',   'index'],
    'GET /api/inventario/exportar'         => ['InventarioController',   'exportarCSV'],
    'GET /api/reportes/historial'          => ['ReporteController',      'historial'],
    'GET /api/buscar'                      => ['BuscarController',       'search'],
    'GET /api/configuracion/validar'       => ['ConfiguracionController','validarIntegridad'],
    'POST /api/configuracion/recalcular'   => ['ConfiguracionController','recalcularStock'],
    'POST /api/configuracion/inicializar'  => ['ConfiguracionController','inicializar'],
    'GET /api/usuarios'                    => ['UsuarioController',      'index'],
    'POST /api/usuarios'                   => ['UsuarioController',      'store'],
    'POST /api/usuarios/actualizar'        => ['UsuarioController',      'update'],
    'GET /api/usuarios/permisos'           => ['UsuarioController',      'permisos'],
    'POST /api/usuarios/permisos'          => ['UsuarioController',      'setPermiso'],
    'POST /api/usuarios/permisos/quitar'   => ['UsuarioController',      'quitarOverride'],
    'GET /app'                             => ['AppController',          'index'],
    'POST /api/usuarios/eliminar' => ['UsuarioController', 'destroy'],
];

if (isset($routes[$key])) {
    [$ctrl, $action] = $routes[$key];
    (new $ctrl())->$action();
    exit;
}

if (str_starts_with($uri, '/app')) {
    (new AppController())->index();
    exit;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => false, 'uri' => $uri, 'key' => $key, 'base' => $scriptBase]);