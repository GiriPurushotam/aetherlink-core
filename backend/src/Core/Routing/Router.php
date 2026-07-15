<?php

declare(strict_types=1);

namespace AetherLink\Core\Routing;

use AetherLink\Core\Container\Container;
use AetherLink\Core\Http\Request;
use AetherLink\Core\Http\Response;
use ReflectionClass;
use RuntimeException;

final class Router
{
    /**
     * @var array<string, array<string, array{controller: class-string, action: string}>> 
     *
     * @param Container $container
     */
    private array $routes = [];
    public function __construct(
        private Container $container
    ) {}

    /**
     * Register a controller to scan it's method for native #[Route] attributes.
     *
     * @param string $controllerClass
     * @return void
     */
    public function registerController(string $controllerClass): void
    {
        $reflection = new ReflectionClass($controllerClass);
        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(Route::class);

            foreach ($attributes as $attribute) {
                /** @var Route $routeInstance */
                $routeInstance = $attribute->newInstance();

                // Normalizing route mapping paths for matching optimization
                $path = rtrim($routeInstance->path, '/');
                $path = $path === '' ? '/' : $path;

                $this->routes[strtoupper($routeInstance->method)][$path] = [
                    'controller' => $controllerClass,
                    'action' => $method->getName()
                ];
            }
        }
    }

    /**
     * Resolve the incoming HTTP request path against the internal compiled map.
     */

    public function dispatch(Request $request): Response
    {
        $method = strtoupper($request->method);
        $path = rtrim(parse_url($request->uri, PHP_URL_PATH), '/');
        $path = $path === '' ? '/' : $path;

        if (!isset($this->routes[$method][$path])) {
            header("HTTP/1.1 404 Not Found");
            return Response::json(['error' => 'Rresource route not found inside the Aetherlink system architecture.']);
        }

        $route = $this->routes[$method][$path];

        $controllerInstance = $this->container->make($route['controller']);
        $action = $route['action'];

        $response = $controllerInstance->$action($request);

        if (!$response instanceof Response) {
            throw new RuntimeException(sprintf('Controller action %s::%s failed to return a valid Http/Response object contract.', $route['controller'], $action));
        }

        return $response;
    }
}
