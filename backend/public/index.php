<?php

declare(strict_types=1);

use AetherLink\Core\Config\Env;
use AetherLink\Core\Container\Container;
use AetherLink\Core\Controllers\UserController;
use AetherLink\Core\Database\DatabaseConfig;
use AetherLink\Core\Database\DatabaseConnection;
use AetherLink\Core\Database\DatabaseConnectionInterface;
use AetherLink\Core\Exceptions\InvalidEnvironmentException;
use AetherLink\Core\Http\Request;
use AetherLink\Core\Routing\Router;

//1. Core Lifecycle: Ingest the freshly compiled PSR-4 autoloader matrix
require_once __DIR__ . '/../vendor/autoload.php';


// -----------Global bootstrap phase (Runs once on worker startup)--------------------------//


try {
    Env::load(dirname(__DIR__) . '/.env');
    $dbConfig = DatabaseConfig::createFromEnv();
    $container = new Container();

    //Register Database configuration and connection Singleton
    $container->singleton(DatabaseConfig::class, static fn(): DatabaseConfig => $dbConfig);

    $container->singleton(DatabaseConnectionInterface::class, static function (Container $c): DatabaseConnection {
        /** @var  DatabaseConfig $config */
        $config = $c->make(DatabaseConfig::class);

        return new DatabaseConnection(
            dsn: $config->getDsn(),
            username: $config->username,
            password: $config->password
        );
    });

    $container->singleton(Router::class, static function (Container $c): Router {
        $router = new Router($c);

        $router->registerController(UserController::class);
        return $router;
    });
} catch (InvalidEnvironmentException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'CRITICAL_BOOT_FALUT',
        'message' => $e->getMessage(),
    ], JSON_PRETTY_PRINT);
    exit(1);
}
// Request Handling and Worker loop
$handler = static function () use ($container): void {
    $db = $container->make(DatabaseConnectionInterface::class);

    try {
        $request = Request::capture();

        $router = $container->make(Router::class);
        $response = $router->dispatch($request);
        $response->send();
    } finally {
        $db->resetState();
    }
};


//Frankenphp long running loop execution check.
if (function_exists('frankenphp_handle_request')) {
    try {
        $maxRequest = (int) ($_SERVER['MAX_REQUESTS'] ?? 0);
        for ($nbRequests = 0; !$maxRequest || $nbRequests < $maxRequest; ++$nbRequests) {
            $keepRunning = frankenphp_handle_request($handler);
            gc_collect_cycles();
            if (!$keepRunning) {
                break;
            }
        }
    } catch (\RuntimeException $e) {
        $handler();
    }
} else {
    //Standerd PHP-FPM / CLI Fallback
    $handler();
}
