<?php

namespace Core;

class Router
{
    private array $routes = [];
    private ?Request $request = null;

    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $this->request = new Request();
    }

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->routes[] = ['GET', $path, $handler, $middleware];
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->routes[] = ['POST', $path, $handler, $middleware];
    }

    public function put(string $path, string $handler, array $middleware = []): void
    {
        $this->routes[] = ['PUT', $path, $handler, $middleware];
    }

    public function delete(string $path, string $handler, array $middleware = []): void
    {
        $this->routes[] = ['DELETE', $path, $handler, $middleware];
    }

    public function dispatch(): void
    {
        $method = $this->request->method;
        $uri    = $this->normalizeUri($this->request->uri);

        foreach ($this->routes as [$routeMethod, $routePath, $handler, $middleware]) {
            if ($routeMethod !== $method) continue;

            $params = $this->match($routePath, $uri);
            if ($params === null) continue;

            // Inject route params into request
            $request = new Request();
            foreach ($params as $k => $v) {
                // Use reflection-free approach: expose via global
                $_GET['_route_' . $k] = $v;
            }

            // Run middleware
            $mw = new Middleware($request, $middleware);
            if (!$mw->handle()) return;

            [$controllerName, $action] = explode('@', $handler);
            $fqcn = 'Controllers\\' . $controllerName;

            if (!class_exists($fqcn)) {
                Response::error("Controller $controllerName not found", 500);
            }

            $controller = new $fqcn();
            if (!method_exists($controller, $action)) {
                Response::error("Action $action not found on $controllerName", 500);
            }

            $controller->$action($request, $params);
            return;
        }

        // 404
        if (str_starts_with($uri, '/api/')) {
            Response::notFound("Route not found: $method $uri");
        } else {
            http_response_code(404);
            require SRC_PATH . '/templates/404.php';
            exit;
        }
    }

    private function normalizeUri(string $uri): string
    {
        // Strip base path (e.g. /PROJETS/hotel-residence-sync/public)
        $base = parse_url(APP_URL, PHP_URL_PATH) ?? '';
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        return '/' . trim($uri, '/') ?: '/';
    }

    private function match(string $routePath, string $uri): ?array
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $uri, $matches)) {
            return null;
        }

        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }
}
