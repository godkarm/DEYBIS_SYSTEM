<?php
/**
 * debug.php v2 — Diagnóstico específico de sesión en Windows/XAMPP
 * Acceder: http://localhost/deybis_system/public/debug.php
 */
echo "<style>body{font-family:monospace;padding:20px;background:#0D1526;color:#C9D8F0}
h2{color:#60A5FA}h3{color:#34D399;margin-top:20px}
.ok{color:#34D399}.err{color:#F87171}.warn{color:#FCD34D}
pre{background:#111E35;padding:12px;border-radius:8px;white-space:pre-wrap}
</style>";
echo "<h2>DEYBIS SYSTEM — Debug Sesión</h2>";

// ── 1. Problema principal: session_save_path ───────────────────
echo "<h3>1. Paths de sesión</h3><pre>";
$rawPath = session_save_path();
echo "session_save_path (raw): " . var_export($rawPath, true) . "\n";

// Resolver path absoluto
$resolved = realpath($rawPath);
echo "realpath():              " . var_export($resolved, true) . "\n";
echo "Existe el directorio:    ";
if ($resolved && is_dir($resolved)) {
    echo "<span class='ok'>SÍ ✓</span>\n";
    echo "Escribible:              ";
    echo is_writable($resolved)
        ? "<span class='ok'>SÍ ✓</span>\n"
        : "<span class='err'>NO ✗ — este es el problema</span>\n";
} else {
    echo "<span class='err'>NO ✗ — PHP no puede encontrar la carpeta de sesiones</span>\n";
    echo "<span class='warn'>→ El path '{$rawPath}' no existe o no es accesible desde PHP</span>\n";
}
echo "</pre>";

// ── 2. Forzar path correcto y probar ──────────────────────────
echo "<h3>2. Test con path forzado</h3><pre>";
$forcedPath = 'C:/xampp/tmp';
if (!is_dir($forcedPath)) {
    $forcedPath = sys_get_temp_dir();
}
echo "Path forzado: $forcedPath\n";
echo "Existe:       " . (is_dir($forcedPath) ? "<span class='ok'>SÍ</span>" : "<span class='err'>NO</span>") . "\n";
echo "Escribible:   " . (is_writable($forcedPath) ? "<span class='ok'>SÍ</span>" : "<span class='err'>NO</span>") . "\n";

// Forzar el path y arrancar sesión
session_save_path($forcedPath);
$cookiePath = '/deybis_system/public/';
session_set_cookie_params([
    'lifetime' => 3600,
    'path'     => $cookiePath,
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (!isset($_SESSION['contador'])) $_SESSION['contador'] = 0;
$_SESSION['contador']++;

echo "\nsession_id():    " . session_id() . "\n";
echo "Contador:        <span class='" . ($_SESSION['contador'] > 1 ? 'ok' : 'warn') . "'>"
     . $_SESSION['contador']
     . ($_SESSION['contador'] > 1 ? " ✓ (sesión persiste)" : " ← recarga la página, debe aumentar")
     . "</span>\n";

// ── 3. Escribir archivo de test manualmente ───────────────────
echo "</pre><h3>3. Test de escritura directa en tmp</h3><pre>";
$testFile = $forcedPath . '/deybis_test_' . time() . '.txt';
$written  = @file_put_contents($testFile, 'test');
if ($written !== false) {
    echo "<span class='ok'>✓ Escritura OK en $forcedPath</span>\n";
    @unlink($testFile);
} else {
    echo "<span class='err'>✗ No se puede escribir en $forcedPath</span>\n";
    echo "<span class='warn'>→ Prueba con sys_get_temp_dir(): " . sys_get_temp_dir() . "</span>\n";
}

// ── 4. php.ini activo ─────────────────────────────────────────
echo "</pre><h3>4. php.ini relevante</h3><pre>";
echo "php.ini cargado:         " . php_ini_loaded_file() . "\n";
echo "session.save_path (ini): " . ini_get('session.save_path') . "\n";
echo "session.save_handler:    " . ini_get('session.save_handler') . "\n";
echo "session.use_cookies:     " . ini_get('session.use_cookies') . "\n";
echo "session.use_strict_mode: " . ini_get('session.use_strict_mode') . "\n";
echo "sys_get_temp_dir():      " . sys_get_temp_dir() . "\n";
echo "</pre>";

// ── 5. Diagnóstico y solución ─────────────────────────────────
echo "<h3>5. Diagnóstico y solución</h3><pre>";
$iniPath = ini_get('session.save_path');
if (empty($iniPath) || !is_dir(realpath($iniPath))) {
    echo "<span class='err'>PROBLEMA DETECTADO: session.save_path está mal configurado</span>\n\n";
    echo "<span class='ok'>SOLUCIÓN — editar php.ini:</span>\n";
    echo "  Archivo: " . php_ini_loaded_file() . "\n";
    echo "  Buscar:  session.save_path\n";
    echo "  Cambiar a: session.save_path = \"C:/xampp/tmp\"\n\n";
    echo "  Después reiniciar Apache desde el panel XAMPP.\n";
} else {
    echo "<span class='ok'>session.save_path parece correcto</span>\n";
    echo "El problema puede estar en permisos de la carpeta.\n";
}
echo "</pre>";
