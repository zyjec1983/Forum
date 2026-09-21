<?php
/**
 * Enrutador simple: mapea método HTTP + URL hacia controladores.
 */
class Router
{
    protected $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, $handler): void
    {
        $this->routes['GET']['/' . ltrim($path, '/')] = $handler;
    }

    public function post(string $path, $handler): void
    {
        $this->routes['POST']['/' . ltrim($path, '/')] = $handler;
    }

    public function dispatch(string $method, string $url): void
    {
        $method = strtoupper($method);
        $path   = '/' . ltrim($url, '/');
        $routes = $this->routes[$method] ?? [];
        $path   = $path === '/' ? '/' : rtrim($path, '/');

        if (isset($routes[$path])) {
            [$class, $action] = $routes[$path];
            $controller = new $class();
            $controller->{$action}();
            return;
        }

        if ($method === 'GET') {
            http_response_code(404);
            View::render('errors/404');
        } else {
            json_out(['ok' => false, 'message' => 'Route not found'], 404);
        }
    }
}