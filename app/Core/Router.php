<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Fast Regex Router with Middleware Pipelines and Parameter Resolution
 */
class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private array $groupStack = [];
    private array $globalMiddleware = [];

    public function use(string|callable $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    public function get(string $path, callable|array|string $action, array $middleware = [], ?string $name = null): self
    {
        return $this->addRoute('GET', $path, $action, $middleware, $name);
    }

    public function post(string $path, callable|array|string $action, array $middleware = [], ?string $name = null): self
    {
        return $this->addRoute('POST', $path, $action, $middleware, $name);
    }

    public function put(string $path, callable|array|string $action, array $middleware = [], ?string $name = null): self
    {
        return $this->addRoute('PUT', $path, $action, $middleware, $name);
    }

    public function delete(string $path, callable|array|string $action, array $middleware = [], ?string $name = null): self
    {
        return $this->addRoute('DELETE', $path, $action, $middleware, $name);
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $path, callable|array|string $action, array $middleware, ?string $name): self
    {
        $prefix = '';
        $groupMiddleware = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $m = is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']];
                $groupMiddleware = array_merge($groupMiddleware, $m);
            }
        }

        $fullPath = rtrim($prefix . '/' . trim($path, '/'), '/');
        if ($fullPath === '') {
            $fullPath = '/';
        }

        $allMiddleware = array_merge($groupMiddleware, $middleware);

        // Convert {param} to regex pattern
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $regex = '#^' . $pattern . '$#';

        $route = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'regex' => $regex,
            'action' => $action,
            'middleware' => $allMiddleware,
            'name' => $name,
        ];

        $this->routes[] = $route;

        if ($name !== null) {
            $this->namedRoutes[$name] = $route;
        }

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        return $this->runMiddlewarePipeline($this->globalMiddleware, $request, function (Request $req): Response {
            return $this->dispatchRoute($req);
        });
    }

    private function dispatchRoute(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        $allowedMethods = [];
        $matchedRoute = null;
        $routeParams = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                $allowedMethods[] = $route['method'];
                if ($route['method'] === $method) {
                    $matchedRoute = $route;
                    // Extract named capture groups
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $routeParams[$key] = $value;
                        }
                    }
                    break;
                }
            }
        }

        if ($matchedRoute === null) {
            if (!empty($allowedMethods)) {
                return Response::json([
                    'error' => 'Method Not Allowed',
                    'allowed_methods' => array_unique($allowedMethods)
                ], 405);
            }

            if ($request->isJson() || $request->isAjax()) {
                return Response::json(['error' => 'Resource Not Found', 'code' => 'NOT_FOUND'], 404);
            }

            $view = new View();
            return Response::html($view->render('errors/404'), 404);
        }

        // Attach route params to request attributes
        foreach ($routeParams as $key => $val) {
            $request->setAttribute($key, $val);
        }

        // Build route-specific middleware pipeline
        return $this->runMiddlewarePipeline($matchedRoute['middleware'], $request, function (Request $req) use ($matchedRoute, $routeParams): Response {
            return $this->executeAction($matchedRoute['action'], $req, $routeParams);
        });
    }

    private function runMiddlewarePipeline(array $middlewareList, Request $request, callable $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($middlewareList),
            function ($next, $middleware) {
                return function (Request $req) use ($next, $middleware): Response {
                    if (is_string($middleware)) {
                        $instance = new $middleware();
                        return $instance->handle($req, $next);
                    } elseif (is_callable($middleware)) {
                        return $middleware($req, $next);
                    }
                    return $next($req);
                };
            },
            $destination
        );

        return $pipeline($request);
    }

    private function executeAction(callable|array|string $action, Request $request, array $routeParams): Response
    {
        if (is_callable($action)) {
            $result = $action($request, ...array_values($routeParams));
        } elseif (is_array($action)) {
            [$controllerClass, $method] = $action;
            $controller = new $controllerClass();
            $result = $controller->$method($request, ...array_values($routeParams));
        } elseif (is_string($action)) {
            if (str_contains($action, '@')) {
                [$controllerName, $method] = explode('@', $action, 2);
                $controllerClass = str_starts_with($controllerName, 'App\\')
                    ? $controllerName
                    : 'App\\Controllers\\' . $controllerName;
                $controller = new $controllerClass();
                $result = $controller->$method($request, ...array_values($routeParams));
            } else {
                $controllerClass = $action;
                $controller = new $controllerClass();
                $result = $controller($request, ...array_values($routeParams));
            }
        } else {
            return Response::json(['error' => 'Invalid action handler'], 500);
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html((string)$result);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
