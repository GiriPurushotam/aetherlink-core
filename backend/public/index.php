<?php

declare(strict_types=1);

use AetherLink\Core\Config\Env;
use AetherLink\Core\Container\Container;
use AetherLink\Core\Controllers\UserController;
use AetherLink\Core\Database\DatabaseConfig;
use AetherLink\Core\Database\DatabaseConnection;
use AetherLink\Core\Exceptions\InvalidEnvironmentException;
use AetherLink\Core\Http\Request;
use AetherLink\Core\Routing\Router;
use AetherLink\Core\Services\UserRepository;

//1. Core Lifecycle: Ingest the freshly compiled PSR-4 autoloader matrix
require_once __DIR__ . '/../vendor/autoload.php';


// ------------------ Core Boot and fail-fast environment variable phase --------------------------//

try {
    Env::load(dirname(__DIR__) . '/.env');
    $dbConfig = DatabaseConfig::createFromEnv();
    $container = new Container();
    $container->singleton(DatabaseConfig::class, static fn(): DatabaseConfig => $dbConfig);

    $container->singleton(DatabaseConnection::class, static function (Container $c): DatabaseConnection {
        /** @var  DatabaseConfig $config */
        $config = $c->make(DatabaseConfig::class);

        return new DatabaseConnection($config);
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

$router = new Router($container);

$router->registerController(UserController::class);


$request = Request::createFromGlobals();
$response = $router->dispatch($request);

$response->send();
