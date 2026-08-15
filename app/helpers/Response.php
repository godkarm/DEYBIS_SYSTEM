<?php
class Response {
    public static function json(mixed $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function ok(mixed $data = null, string $mensaje = 'OK'): void {
        self::json(['ok' => true, 'mensaje' => $mensaje, 'data' => $data]);
    }

    public static function error(string $mensaje, int $status = 400): void {
        self::json(['ok' => false, 'mensaje' => $mensaje], $status);
    }

    public static function view(string $viewPath, array $data = []): void {
        extract($data);
        $fullPath = ROOT . '/app/views/' . $viewPath . '.php';
        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "Vista no encontrada: $viewPath";
            exit;
        }
        require $fullPath;
    }
}
