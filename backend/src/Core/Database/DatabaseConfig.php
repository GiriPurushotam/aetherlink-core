<?php

declare(strict_types=1);

namespace AetherLink\Core\Database;

use RuntimeException;

readonly final class DatabaseConfig
{
    public function __construct(
        public string $driver   = 'pgsql',
        public string $host     = 'aether_db', // Matches the internal docker service name inside aether_private
        public int $port        = 5432,
        public string $database = 'aetherlink_dev',
        public string $username = 'aether_admin',
        public string $password = 'secret_development_password',
        public string $charset  = 'utf8'
    ) {}


    /**
     * Factory Constructor to forge configuration directly out of system environment variables.
     */

    public static function createFromEnv(): self
    {
        return new self(
            driver: getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? 'pgsql'),
            host: getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? throw new RuntimeException('Infrastructure Error: [DB_HOST] environment target undefined.')),
            port: (int) (getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? 5432)),
            database: getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? throw new RuntimeException('Infrastructure Error: Target [DB_DATABASE] definition missing')),
            username: getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? throw new RuntimeException('Intrastructure Error: Target [DB_USERNAME] parameter missing')),
            password: getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? throw new RuntimeException('Critical Infrastructure Error: Target [DB_PASSWORD] in completely missing or unreadable. ')),
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
