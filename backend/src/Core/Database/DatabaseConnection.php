<?php

declare(strict_types=1);

namespace AetherLink\Core\Database;

use PDO;
use PDOException;
use RuntimeException;

final class DatabaseConnection
{
    private ?PDO $pdo = null;

    public function __construct(
        private DatabaseConfig $config
    ) {}

    /**
     * Lazily establish and return the active PDO connection instance.
     */

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            try {
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false, // Critical for SQL injection protecion
                    PDO::ATTR_PERSISTENT => true, // Reuses database connection channels in worker mode
                ];

                $this->pdo = new PDO(
                    $this->config->getDsn(),
                    $this->config->username,
                    $this->config->password,
                    $options
                );
            } catch (PDOException $e) {
                throw new RuntimeException(sprintf('Database Connection Error [PostgreSQL]: %s', $e->getMessage()), (int) $e->getCode(), $e);
            }
        }

        return $this->pdo;
    }

    /**
     * Direct wrapper for safe prepared query execution.
     *
     * @param string $sql The SQL query string with parameter placeholders (?)
     * @param array<int|string, mixed> $params Parameters to bind
     * @return array<int, array<string, mixed>>
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
