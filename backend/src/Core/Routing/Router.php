<?php

declare(strict_types=1);

namespace AetherLink\Core\Routing;

use AetherLink\Core\Container\Container;
use AetherLink\Core\Http\MiddlewareInterface;
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
                    'action' => $method->getName(),
                    'middleware' => $routeInstance->middleware // Save Configure Middleware
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
            return Response::json(['error' => 'Rresource route not found inside the Aetherlink system architecture.'], 404);
        }

        $route = $this->routes[$method][$path];

        //1.Compile the core controller action layer as the absolute core of the onion
        $coreExecutionNode = function (Request $req) use ($route): Response {
            $controllerInstance = $this->container->make($route['controller']);
            $action = $route['action'];
            return $controllerInstance->$action($req);
        };

        // 2. Reverse the middleware array to build the recursive wrapped execution chain from the inside out.
        $pipeline = $coreExecutionNode;
        $middlewareStack = array_reverse($route['middleware'] ?? []);
        foreach ($middlewareStack as $middlewareClass) {
            $pipeline = function (Request $req) use ($middlewareClass, $pipeline): Response {
                // Resolve the middleware out of the DI container with auto-wiring capibilities 
                $middleInstance = $this->container->make($middlewareClass);

                if (!$middleInstance instanceof MiddlewareInterface) {
                    throw new RuntimeException(sprintf('Class [%s] must implement MiddlewareInterface. ', $middlewareClass));
                }

                return $middleInstance->handle($req, $pipeline);
            };
        }
        return $pipeline($request);
    }
}
