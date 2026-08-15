<?php
/**
 * config/app.php — Constantes globales de la aplicación
 *
 * ── XAMPP LOCAL ───────────────────────────────────────────────
 * Si el proyecto está en:  C:/xampp/htdocs/deybis_system/
 * URL de acceso:           http://localhost/deybis_system/public/
 * BASE_URL debe ser:       /deybis_system/public
 *
 * Si moviste la carpeta, ajusta BASE_URL al path real.
 * ─────────────────────────────────────────────────────────────
 */

define('APP_NAME',    'DEYBIS SYSTEM');
define('APP_VERSION', '2.0.0');
define('APP_ENV',     'development');   // 'development' | 'production'

// ── URL base del proyecto ─────────────────────────────────────
// Sin trailing slash. Debe coincidir con RewriteBase en .htaccess
define('BASE_URL', '/deybis_system/public');

// ── Sesión ───────────────────────────────────────────────────
define('SESSION_LIFETIME', 8 * 3600);   // 8 horas

// ── Roles ────────────────────────────────────────────────────
define('ROLES', [
    'ADMINISTRADOR' => 1,
    'ALMACENERO'    => 2,
    'CLIENTE'       => 3,
]);

// ── Tipos de movimiento ───────────────────────────────────────
define('TIPOS_MOVIMIENTO', [
    'INGRESO', 'SALIDA',
    'AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO', 'AJUSTE',
]);

// ── Prefijos de grupo (fuente de verdad única) ────────────────
define('PREFIJOS_POR_GRUPO', [
    'Agroquímico'          => 'AGR',
    'Combustible'          => 'GAS',
    'Computo'              => 'COM',
    'Economato'            => 'ECO',
    'EPP'                  => 'EPP',
    'Ferretería'           => 'FER',
    'Limpieza'             => 'UTL',
    'Material de embalaje' => 'EMB',
    'Material de empaque'  => 'EMP',
    'Repuestos'            => 'REP',
    'Útiles de oficina'    => 'UTO',
]);
