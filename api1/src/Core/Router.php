<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, array $handler, array $middlewares = []): void
    {
        $this->routes[] = compact('method', 'pattern', 'handler', 'middlewares');
    }

    public function dispatch(string $method, string $uri, Container $container): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $pattern = '#^' . $route['pattern'] . '$#';
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                // Middlewares
                foreach ($route['middlewares'] as $mw) {
                    $mw->handle();
                }
                [$class, $action] = $route['handler'];
                $controller = $container->get($class);
                $response = $controller->$action(...$matches);
                $this->sendJson($response);
                return;
            }
        }
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    }

    private function sendJson($data): void
    {
        header('Content-Type: application/json');
        if (is_array($data) || is_object($data)) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo (string)$data;
        }
    }
}
