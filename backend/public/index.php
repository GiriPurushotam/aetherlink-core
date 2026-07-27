<?php

declare(strict_types=1);

use AetherLink\Core\Container\Container;
use AetherLink\Core\Database\DatabaseConnectionInterface;
use AetherLink\Core\Exceptions\InvalidEnvironmentException;
use AetherLink\Core\Http\Request;
use AetherLink\Core\Kernel\AppKernel;
use AetherLink\Core\Routing\Router;

//1. Core Lifecycle: Ingest the freshly compiled PSR-4 autoloader matrix
require_once __DIR__ . '/../vendor/autoload.php';


// -----------Global bootstrap phase (Runs once on worker startup)--------------------------//


try {
    $kernel = new AppKernel();
    $container = $kernel->boot(dirname(__DIR__));
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
