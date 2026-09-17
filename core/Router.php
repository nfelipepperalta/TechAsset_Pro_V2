<?php
// ============================================================
//  core/Router.php
//  Enrutador — mapea URLs a Controllers y métodos
// ============================================================

namespace Core;

class Router
{
    private array $routes = [];

    // Registrar una ruta GET
    public function get(string $path, string $controller, string $method): void
    {
        $this->addRoute('GET', $path, $controller, $method);
    }

    // Registrar una ruta POST
    public function post(string $path, string $controller, string $method): void
    {
        $this->addRoute('POST', $path, $controller, $method);
    }

    // Registrar rutas GET y POST al mismo tiempo
    public function any(string $path, string $controller, string $method): void
    {
        $this->addRoute('GET',  $path, $controller, $method);
        $this->addRoute('POST', $path, $controller, $method);
    }

    private function addRoute(string $httpMethod, string $path, string $controller, string $method): void
    {
        $this->routes[] = [
            'httpMethod'  => $httpMethod,
            'path'        => $path,
            'controller'  => $controller,
            'method'      => $method,
            'pattern'     => $this->pathToPattern($path),
        ];
    }

    // Convertir path con parámetros a regex — ej: /assets/{id} → /assets/(\d+)
    private function pathToPattern(string $path): string
    {
        $pattern = preg_replace('/\{id\}/',   '(\d+)',      $path);
        $pattern = preg_replace('/\{slug\}/', '([a-z0-9-]+)', $pattern);
        $pattern = preg_replace('/\{token\}/', '([a-zA-Z0-9_-]+)', $pattern);
        $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)',    $pattern);
        return '#^' . $pattern . '$#';
    }

    // Despachar la petición actual al controller correcto
    public function dispatch(): void
    {
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri        = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Normalizar URI: quitar prefijo de APP_URL si existe
        $basePath = parse_url(APP_URL, PHP_URL_PATH) ?? '';
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = '/' . trim($uri, '/');
        if ($uri !== '/') $uri = rtrim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['httpMethod'] !== $httpMethod) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches); // quitar match completo

                $controllerClass = 'Controllers\\' . $route['controller'];

                if (!class_exists($controllerClass)) {
                    Response::error("Controlador no encontrado: {$route['controller']}", 500);
                }

                $controller = new $controllerClass();
                $method     = $route['method'];

                if (!method_exists($controller, $method)) {
                    Response::error("Método no encontrado: {$method}", 500);
                }

                call_user_func_array([$controller, $method], $matches);
                return;
            }
        }

        // Ninguna ruta coincidió — 404
        http_response_code(404);
        Response::view('errors/404');
    }
}
