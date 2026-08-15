<?php

namespace App\Core;

/**
 * Router
 *
 * Simple regex-free path matching with support for {param} placeholders,
 * per-route middleware, and a 404 fallback.
 */
class Router
{
    /** @var array<int,array{method:string,pattern:string,handler:mixed,middleware:array<int,string>}> */
    private array $routes = [];

    /** @var array<string,string> Map of middleware alias => class name */
    private array $middlewareMap = [];

    public function registerMiddleware(string $alias, string $class): void
    {
        $this->middlewareMap[$alias] = $class;
    }

    /**
     * @param array<int,string> $middleware
     */
    public function add(string $method, string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => rtrim($pattern, '/') ?: '/',
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * @param array<int,string> $middleware
     */
    public function get(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    /**
     * @param array<int,string> $middleware
     */
    public function post(string $pattern, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    /**
     * Match and dispatch the request.
     */
    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path   = $request->path();
        $pathMatched = false;

        foreach ($this->routes as $route) {
            $params = $this->matchPath($route['pattern'], $path);
            if ($params === null) {
                continue;
            }
            $pathMatched = true;

            if ($route['method'] !== $method) {
                continue;
            }

            // Run middleware chain.
            foreach ($route['middleware'] as $alias) {
                $class = $this->middlewareMap[$alias] ?? null;
                if ($class === null || !class_exists($class)) {
                    continue;
                }
                /** @var object $mw */
                $mw = new $class();
                $mw->handle($request);
            }

            $this->invoke($route['handler'], $request, $params);
            return;
        }

        // Path exists but wrong method vs. truly not found.
        if ($pathMatched) {
            $this->notAllowed($request);
        }
        $this->notFound($request);
    }

    /**
     * Attempt to match a route pattern against a path.
     *
     * @return array<string,string>|null Extracted params, or null on no match.
     */
    private function matchPath(string $pattern, string $path): ?array
    {
        $patternParts = explode('/', trim($pattern, '/'));
        $pathParts    = explode('/', trim($path, '/'));

        if (count($patternParts) !== count($pathParts)) {
            return null;
        }

        $params = [];
        foreach ($patternParts as $i => $seg) {
            $current = $pathParts[$i] ?? '';
            if (str_starts_with($seg, '{') && str_ends_with($seg, '}')) {
                $name = substr($seg, 1, -1);
                $params[$name] = $current;
                continue;
            }
            if ($seg !== $current) {
                return null;
            }
        }
        return $params;
    }

    /**
     * @param array<string,string> $params
     */
    private function invoke(mixed $handler, Request $request, array $params): void
    {
        if (is_callable($handler)) {
            $handler($request, $params);
            return;
        }

        // "Controller@method" string form.
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            if (class_exists($class)) {
                $instance = new $class();
                if (method_exists($instance, $method)) {
                    $instance->{$method}($request, $params);
                    return;
                }
            }
        }

        $this->notFound($request);
    }

    private function notFound(Request $request): void
    {
        if ($request->isApi()) {
            Response::error('Endpoint not found', 404);
        }
        Response::html('<h1>404 Not Found</h1>', 404);
    }

    private function notAllowed(Request $request): void
    {
        if ($request->isApi()) {
            Response::error('Method not allowed', 405);
        }
        Response::html('<h1>405 Method Not Allowed</h1>', 405);
    }
}
