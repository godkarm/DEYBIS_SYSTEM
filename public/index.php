<?php
/**
 * public/index.php — Front Controller DEYBIS SYSTEM
 * Versión con log de debug para diagnosticar redirect loop
 */

define('ROOT', dirname(__DIR__));
require ROOT . '/config/app.php';

// ── LOG de cada request (temporal para debug) ─────────────────
$logFile = ROOT . '/storage/logs/debug_requests.log';
$logDir  = dirname($logFile);
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

// ── 1. Fijar session.save_path con ruta absoluta ──────────────
$iniPath = ini_get('session.save_path');
// Detectar si el path es relativo (sin letra de unidad en Windows)
$needsFix = empty($iniPath)
    || (!is_dir($iniPath) && !is_dir(@realpath($iniPath)));

if ($needsFix) {
    $candidates = [
        'C:/xampp/tmp',
        'C:/XAMPP/tmp',
        'D:/xampp/tmp',
        'D:/XAMPP/tmp',
        sys_get_temp_dir(),
        ROOT . '/storage/sessions',
    ];
    foreach ($candidates as $c) {
        if (is_dir($c) && is_writable($c)) {
            session_save_path($c);
            break;
        }
    }
    // Fallback: crear dentro del proyecto
    $fallback = ROOT . '/storage/sessions';
    if (!is_dir($fallback)) @mkdir($fallback, 0755, true);
    if (is_dir($fallback) && !session_save_path()) {
        session_save_path($fallback);
    }
}

// ── 2. Cookie path ────────────────────────────────────────────
$scriptBase        = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$sessionCookiePath = ($scriptBase !== '' ? $scriptBase : '/') . '/';

session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => $sessionCookiePath,
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// ── 3. LOG ────────────────────────────────────────────────────
$logEntry = date('H:i:s') . ' | '
    . $_SERVER['REQUEST_METHOD'] . ' '
    . $_SERVER['REQUEST_URI'] . ' | '
    . 'SID=' . (session_id() ?: 'NONE') . ' | '
    . 'SESSION=' . json_encode($_SESSION) . ' | '
    . 'COOKIE=' . json_encode($_COOKIE) . ' | '
    . 'save_path=' . session_save_path()
    . "\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND);

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
$rawUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
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
$key    = "$method $uri";

// ── 5. Rutas públicas ─────────────────────────────────────────
$publicRoutes = [
    'GET  /'           => ['AuthController', 'showLogin'],
    'POST /api/login'  => ['AuthController', 'login'],
    'POST /api/logout' => ['AuthController', 'logout'],
];

if (isset($publicRoutes[$key])) {
    [$ctrl, $action] = $publicRoutes[$key];
    (new $ctrl())->$action();
    exit;
}

// ── 6. Protegidas ─────────────────────────────────────────────
Auth::requireLogin();

$routes = [
    'GET  /api/dashboard/resumen'           => ['DashboardController',    'resumen'],
    'GET  /api/dashboard/alertas'           => ['DashboardController',    'alertas'],
    'GET  /api/clientes'                    => ['ClienteController',      'index'],
    'POST /api/clientes'                    => ['ClienteController',      'store'],
    'POST /api/clientes/estado'             => ['ClienteController',      'cambiarEstado'],
    'GET  /api/productos/catalogo'          => ['ProductoController',     'catalogo'],
    'POST /api/productos'                   => ['ProductoController',     'store'],
    'POST /api/productos/habilitar'         => ['ProductoController',     'habilitar'],
    'POST /api/productos/deshabilitar'      => ['ProductoController',     'deshabilitar'],
    'GET  /api/productos/buscar'            => ['ProductoController',     'buscar'],
    'GET  /api/productos/listas'            => ['ProductoController',     'listas'],
    'GET  /api/productos/prefijos'          => ['ProductoController',     'prefijos'],
    'POST /api/movimientos'                 => ['MovimientoController',   'store'],
    'GET  /api/movimientos/buscar-producto' => ['MovimientoController',   'buscarProducto'],
    'GET  /api/inventario'                  => ['InventarioController',   'index'],
    'GET  /api/inventario/exportar'         => ['InventarioController',   'exportarCSV'],
    'GET  /api/reportes/historial'          => ['ReporteController',      'historial'],
    'GET  /api/buscar'                      => ['BuscarController',       'search'],
    'GET  /api/configuracion/validar'       => ['ConfiguracionController','validarIntegridad'],
    'POST /api/configuracion/recalcular'    => ['ConfiguracionController','recalcularStock'],
    'POST /api/configuracion/inicializar'   => ['ConfiguracionController','inicializar'],
    'GET  /api/usuarios'                    => ['UsuarioController',      'index'],
    'POST /api/usuarios'                    => ['UsuarioController',      'store'],
    'POST /api/usuarios/actualizar'         => ['UsuarioController',      'update'],
    'GET  /api/usuarios/permisos'           => ['UsuarioController',      'permisos'],
    'POST /api/usuarios/permisos'           => ['UsuarioController',      'setPermiso'],
    'POST /api/usuarios/permisos/quitar'    => ['UsuarioController',      'quitarOverride'],
    'GET  /app'                             => ['AppController',          'index'],
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
