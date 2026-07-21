<?php

declare(strict_types=1);

use AetherLink\Core\Container\Container;
use AetherLink\Core\Controllers\UserController;
use AetherLink\Core\Database\DatabaseConfig;
use AetherLink\Core\Database\DatabaseConnection;
use AetherLink\Core\Http\Request;
use AetherLink\Core\Routing\Router;
use AetherLink\Core\Services\UserRepository;

//1. Core Lifecycle: Ingest the freshly compiled PSR-4 autoloader matrix
require_once __DIR__ . '/../vendor/autoload.php';

// use AetherLink\Core\Kernel;

// //2. Safely extract environment states injected by Docker Compose
// $env = $_ENV['APP_ENV'] ?? 'production';
// $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);

// //3. Instantiate and ignite the structural engine core 
// $kernel = new Kernel($env, $debug);
// $kernel->boot();



// $userRepo = $container->make(UserRepository::class);

// //4. Return an explicit system status contract to the client
// header('Content-Type: application/json; charset=utf-8');

// echo json_encode([
//     'status' => 'online',
//     'service' => 'AetherLink Core Backend Test',
//     'engine' => 'Aetherlink Engine core container test',
//     'result' => $userRepo->getUserData(43)
// ], JSON_THROW_ON_ERROR);


// ------------------ Test ROute Dispatch --------------------------//
$container = new Container();
$container->singleton(DatabaseConfig::class, fn() => DatabaseConfig::createFromEnv());

$container->singleton(DatabaseConnection::class, function (Container $c): DatabaseConnection {
    /** @var  DatabaseConfig $config */
    $config = $c->make(DatabaseConfig::class);

    return new DatabaseConnection($config);
});
$router = new Router($container);

$router->registerController(UserController::class);


$request = Request::createFromGlobals();
$response = $router->dispatch($request);

$response->send();
