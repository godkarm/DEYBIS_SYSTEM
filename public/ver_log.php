<?php
/**
 * ver_log.php — Ver el log de requests en el navegador
 * http://localhost/deybis_system/public/ver_log.php
 * BORRAR después del diagnóstico
 */
$logFile = dirname(__DIR__) . '/storage/logs/debug_requests.log';
echo "<meta charset='utf-8'>";
echo "<style>body{background:#0D1526;color:#C9D8F0;font-family:monospace;padding:20px}
pre{background:#111E35;padding:12px;border-radius:8px;white-space:pre-wrap;word-break:break-all;font-size:12px}
h2{color:#60A5FA}h3{color:#34D399}.err{color:#F87171}.ok{color:#34D399}.warn{color:#FCD34D}</style>";

echo "<h2>DEYBIS SYSTEM — Log de Requests</h2>";

// Borrar log (botón)
if (isset($_GET['clear'])) {
    @unlink($logFile);
    echo "<div class='ok'>✓ Log borrado. <a href='ver_log.php' style='color:#60A5FA'>Recargar</a></div>";
}

echo "<p><a href='ver_log.php?clear=1' style='color:#F87171'>Borrar log</a> | 
      <a href='ver_log.php' style='color:#60A5FA'>Recargar</a> |
      <a href='http://localhost/deybis_system/public/' style='color:#34D399' target='_blank'>
      Abrir sistema (abre en nueva pestaña)</a></p>";

echo "<h3>storage/logs/debug_requests.log</h3>";

if (!file_exists($logFile)) {
    echo "<div class='warn'>⚠ El log aún no existe — accede al sistema primero para generarlo.</div>";
    echo "<p>Ruta esperada: <code>$logFile</code></p>";
    
    // Mostrar qué hay en storage/logs
    $logsDir = dirname(__DIR__) . '/storage/logs';
    echo "<h3>Directorio storage/logs/</h3><pre>";
    if (is_dir($logsDir)) {
        $files = scandir($logsDir);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $fp = $logsDir . '/' . $f;
            echo filesize($fp) . " bytes  $f\n";
        }
    } else {
        echo "El directorio no existe todavía.";
    }
    echo "</pre>";
} else {
    $lines = file($logFile);
    $total = count($lines);
    echo "<p>Total requests: <strong>$total</strong></p>";
    echo "<pre>";
    
    // Mostrar las últimas 30 líneas
    $show = array_slice($lines, -30);
    foreach ($show as $line) {
        // Colorear según contenido
        if (str_contains($line, 'SESSION=[]') || str_contains($line, 'SID=NONE')) {
            echo "<span class='err'>$line</span>";
        } elseif (str_contains($line, 'usuario')) {
            echo "<span class='ok'>$line</span>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
    
    // Análisis automático
    echo "<h3>Análisis automático</h3><pre>";
    $sids = [];
    $sinSesion = 0;
    $conSesion = 0;
    foreach ($lines as $line) {
        preg_match('/SID=(\S+)/', $line, $m);
        $sid = $m[1] ?? 'NONE';
        $sids[$sid] = ($sids[$sid] ?? 0) + 1;
        
        if (str_contains($line, 'SESSION=[]') || str_contains($line, '"usuario"') === false) {
            $sinSesion++;
        } else {
            $conSesion++;
        }
    }
    
    echo "SIDs únicos: " . count($sids) . "\n";
    foreach ($sids as $sid => $count) {
        echo "  $sid → $count requests\n";
    }
    echo "\nRequests SIN sesión de usuario: <span class='" . ($sinSesion > 0 ? 'err' : 'ok') . "'>$sinSesion</span>\n";
    echo "Requests CON sesión de usuario: <span class='" . ($conSesion > 0 ? 'ok' : 'err') . "'>$conSesion</span>\n";
    
    if (count($sids) > 2) {
        echo "\n<span class='err'>⚠ PROBLEMA: Cada request genera un SID diferente → la sesión NO persiste</span>\n";
        echo "<span class='warn'>Causa probable: session.save_path incorrecto en php.ini</span>\n";
    } elseif ($conSesion > 0) {
        echo "\n<span class='ok'>✓ La sesión SÍ persiste correctamente</span>\n";
    }
    echo "</pre>";
}

// Info adicional del entorno actual
echo "<h3>Entorno PHP actual (en este request)</h3><pre>";
echo "session.save_path (ini): " . ini_get('session.save_path') . "\n";
$sp = ini_get('session.save_path');
echo "Existe y escribible:     ";
$rp = realpath($sp);
echo ($rp && is_writable($rp)) 
    ? "<span class='ok'>SÍ — $rp</span>\n" 
    : "<span class='err'>NO — ruta: '$sp' → realpath: " . var_export($rp, true) . "</span>\n";
echo "sys_get_temp_dir():      " . sys_get_temp_dir() . "\n";
echo "php.ini cargado:         " . php_ini_loaded_file() . "\n";
echo "</pre>";
