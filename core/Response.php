<?php
// ============================================================
//  core/Response.php
//  Respuestas HTTP — JSON, redirect, views
// ============================================================

namespace Core;

class Response
{
    // Redirigir a una URL
    public static function redirect(string $path): never
    {
        $url = APP_URL . $path;
        header("Location: $url");
        exit;
    }

    // Respuesta JSON (para peticiones AJAX / API)
    public static function json(mixed $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Respuesta JSON de éxito
    public static function success(mixed $data = null, string $message = 'OK'): never
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    // Respuesta JSON de error
    public static function error(string $message, int $statusCode = 400): never
    {
        self::json(['success' => false, 'message' => $message], $statusCode);
    }

    // Renderizar una vista PHP
    public static function view(string $viewPath, array $data = []): void
    {
        // Extraer variables para que estén disponibles en la vista
        extract($data);

        $fullPath = VIEWS_PATH . '/' . ltrim($viewPath, '/') . '.php';

        if (!file_exists($fullPath)) {
            http_response_code(404);
            die("Vista no encontrada: $viewPath");
        }

        require $fullPath;
    }

    // Renderizar vista dentro del layout principal
    public static function layout(string $viewPath, array $data = [], string $layout = 'layouts/main'): void
    {
        extract($data);
        $content = $viewPath; // La vista se renderiza dentro del layout

        $layoutPath = VIEWS_PATH . '/' . $layout . '.php';
        $viewFull   = VIEWS_PATH . '/' . ltrim($viewPath, '/') . '.php';

        if (!file_exists($viewFull)) {
            http_response_code(404);
            die("Vista no encontrada: $viewPath");
        }

        if (!file_exists($layoutPath)) {
            // Si no hay layout, renderizar la vista directamente
            self::view($viewPath, $data);
            return;
        }

        require $layoutPath;
    }

    // Código de estado HTTP sin body
    public static function status(int $code): never
    {
        http_response_code($code);
        exit;
    }
}
