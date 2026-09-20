<?php
namespace App\Core;

class Router
{
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $path, array|callable $handler, array $middleware = []): void
    { $this->add('GET', $path, $handler, $middleware); }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    { $this->add('POST', $path, $handler, $middleware); }

    public function put(string $path, array|callable $handler, array $middleware = []): void
    { $this->add('PUT', $path, $handler, $middleware); }

    public function patch(string $path, array|callable $handler, array $middleware = []): void
    { $this->add('PATCH', $path, $handler, $middleware); }

    public function delete(string $path, array|callable $handler, array $middleware = []): void
    { $this->add('DELETE', $path, $handler, $middleware); }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function add(string $method, string $path, array|callable $handler, array $middleware = []): void
    {
        $prefix = '';
        $groupMiddleware = [];

        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'] ?? '';
            $groupMiddleware = array_merge($groupMiddleware, $group['middleware'] ?? []);
        }

        $path = '/' . trim($prefix . $path, '/');

        $this->routes[] = [
            'method'     => $method,
            'path'       => $path,
            'handler'    => $handler,
            'middleware' => array_merge($groupMiddleware, $middleware),
        ];
    }

    public function dispatch(Request $req): void
    {
        \App\Core\Auth::setRequest($req);
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $pattern = $this->compile($route['path']);

            if (!preg_match($pattern, $req->path(), $matches)) {
                continue;
            }

            if ($route['method'] !== $req->method()) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            $this->runMiddleware($route['middleware'], $req);

            $params = array_values(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

            if (is_array($route['handler'])) {
                [$class, $method] = $route['handler'];
                $controller = new $class();
                $controller->$method($req, ...$params);
            } else {
                call_user_func($route['handler'], $req, ...$params);
            }

            return;
        }

        if ($allowedMethods) {
            Response::error('Method not allowed', 405, null, [
                'Allow' => implode(', ', array_unique($allowedMethods)),
            ]);
        }

        Response::error('Route not found: ' . $req->path(), 404);
    }

    private function runMiddleware(array $middleware, Request $req): void
    {
        foreach ($middleware as $mw) {
            if (is_string($mw)) {
                if (str_contains($mw, ':')) {
                    $parts = explode(':', $mw);
                    $class = array_shift($parts);
                    $args  = array_map('trim', $parts);

                    if (!class_exists($class)) {
                        throw new \RuntimeException("Middleware class not found: $class");
                    }
                    $class::handle($req, ...$args);
                } else {
                    if (!class_exists($mw)) {
                        throw new \RuntimeException("Middleware class not found: $mw");
                    }
                    $mw::handle($req);
                }
            } elseif (is_callable($mw)) {
                $mw($req);
            } else {
                throw new \RuntimeException('Invalid middleware definition');
            }
        }
    }

    private function compile(string $path): string
    {
        $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '/?$#';
    }
}
