<?php

declare(strict_types=1);

namespace AetherLink\Core\Kernel;

use AetherLink\Core\Config\Env;
use AetherLink\Core\Container\Container;
use AetherLink\Core\Controllers\UserController;
use AetherLink\Core\Database\DatabaseConfig;
use AetherLink\Core\Database\DatabaseConnection;
use AetherLink\Core\Database\DatabaseConnectionInterface;
use AetherLink\Core\Routing\Router;

final class AppKernel
{
    private Container $container;

    public function boot(string $basePath): Container
    {
        //1. Parse and validate environment constrains
        Env::load($basePath . '/.env');

        $this->container = new Container();

        //2. Register database value objects and  Connection Interface.
        $dbConfig = DatabaseConfig::createFromEnv();
        $this->container->singleton(DatabaseConfig::class, static fn(): DatabaseConfig => $dbConfig);

        $this->container->singleton(DatabaseConnectionInterface::class, static function (Container $c): DatabaseConnection {
            /** @var DatabaseConfig $config */
            $config = $c->make(DatabaseConfig::class);

            return new DatabaseConnection(
                dsn: $config->getDsn(),
                username: $config->username,
                password: $config->password
            );
        });

        //3. Register router and scan application controllers
        $this->container->singleton(Router::class, function (Container $c): Router {
            $router = new Router($c);

            // Register RouteControllers here.
            $router->registerController(UserController::class);

            return $router;
        });
        return $this->container;
    }
}
