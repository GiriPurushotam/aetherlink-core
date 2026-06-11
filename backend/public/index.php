<?php

declare(strict_types=1);

use AetherLink\Core\Container\Container;
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

$container = new Container();

$userRepo = $container->make(UserRepository::class);

//4. Return an explicit system status contract to the client
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => 'online',
    'service' => 'AetherLink Core Backend',
    'engine' => 'Aetherlink Engine core container test',
    'result' => $userRepo->getUserData(42)
], JSON_THROW_ON_ERROR);
