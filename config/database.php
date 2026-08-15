<?php
/**
 * config/database.php — Singleton PDO
 *
 * Configuración para XAMPP local:
 *   host     : localhost
 *   usuario  : root
 *   password : (vacío en XAMPP por defecto)
 *   base     : deybis_system
 *
 * Para producción sobreescribir con variables de entorno.
 */
class Database {
    private static ?Database $instance = null;
    private PDO $conn;

    // ── Credenciales XAMPP por defecto ────────────────────────
    // Cambia aquí si tu XAMPP tiene contraseña en root, o
    // usa variables de entorno para producción.
    private string $host     = 'localhost';
    private string $db       = 'deybis_system';
    private string $user     = 'root';
    private string $password = '';           // XAMPP: vacío por defecto
    private string $charset  = 'utf8mb4';
    private int    $port     = 3306;

    private function __construct() {
        // Variables de entorno tienen prioridad (producción/Docker)
        $host = $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?: $this->host;
        $db   = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: $this->db;
        $user = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: $this->user;
        $pass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: $this->password;
        $port = (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: $this->port);

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->conn = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            $msg = 'Error de conexión a la base de datos.';
            // En desarrollo mostrar detalle; en producción ocultar
            if (defined('APP_ENV') && APP_ENV === 'development') {
                $msg .= ' [' . $e->getMessage() . ']';
            }
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'mensaje' => $msg]);
            exit;
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->conn;
    }
}
