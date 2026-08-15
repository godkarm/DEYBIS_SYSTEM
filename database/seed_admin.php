<?php
/**
 * seed_admin.php — Establece la contraseña del usuario admin
 *
 * Ejecutar UNA sola vez desde el navegador o CLI:
 *
 *   Navegador: http://localhost/deybis_system/database/seed_admin.php
 *              (luego borrarlo o moverlo fuera de htdocs)
 *
 *   CLI:  php database/seed_admin.php
 *
 * Contraseña por defecto: admin123
 * ¡Cámbiala desde el panel de Usuarios inmediatamente!
 */

// Cargar config y BD
define('ROOT', dirname(__DIR__));
require ROOT . '/config/app.php';
require ROOT . '/config/database.php';

try {
    $pdo  = Database::getInstance()->getConnection();
    $hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE usuario = 'admin'");
    $stmt->execute([$hash]);

    if ($stmt->rowCount() > 0) {
        echo "✓ Hash actualizado correctamente.<br>Usuario: admin | Contraseña: admin123<br><strong>¡Cambia la contraseña desde el panel de Usuarios!</strong>";
    } else {
        echo "⚠ No se encontró el usuario 'admin'. ¿Ya ejecutaste el schema.sql?";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage();
}
