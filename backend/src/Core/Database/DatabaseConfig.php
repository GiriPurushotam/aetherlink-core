<?php

declare(strict_types=1);

namespace AetherLink\Core\Database;

use AetherLink\Core\Config\Env;

readonly final class DatabaseConfig
{
    public function __construct(
        public string $driver,
        public string $host,
        public int $port,
        public string $database,
        public string $username,
        public string $password,
        public string $charset = 'utf8'
    ) {}


    /**
     * Construct purely from validated environment state. no hardcoded defaults.
     */

    public static function createFromEnv(): self
    {
        return new self(
            driver: Env::get('DB_CONNECTION'),
            host: Env::get('DB_HOST'),
            port: Env::getInt('DB_PORT'),
            database: Env::get('DB_DATABASE'),
            username: Env::get('DB_USERNAME'),
            password: Env::get('DB_PASSWORD'),
            charset: 'utf8'
        );
    }

    /**
     * Build the native PDO Data source name (DSN) connection string.
     *
     * @return string
     */
    public function getDsn(): string
    {
        return sprintf(
            '%s:host=%s;port=%d;dbname=%s;client_encoding=%s',
            $this->driver,
            $this->host,
            $this->port,
            $this->database,
            $this->charset
        );
    }
}
