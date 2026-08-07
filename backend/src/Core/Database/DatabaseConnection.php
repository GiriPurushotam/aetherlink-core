<?php

declare(strict_types=1);

namespace AetherLink\Core\Database;

use Override;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use SensitiveParameter;

final class DatabaseConnection implements DatabaseConnectionInterface
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $dsn,
        #[SensitiveParameter] private readonly string $username,
        #[SensitiveParameter] private readonly string $password,
        private readonly array $options = []
    ) {}


    #[Override]
    public function getPdo(): PDO
    {
        if ($this->pdo === null || !$this->isAlive()) {
            $this->reconnect();
        }

        return $this->pdo;
    }


    #[Override]
    public function query(string $stament): PDOStatement
    {
        try {
            return $this->getPdo()->query($stament);
        } catch (PDOException $e) {
            if ($this->isConnectionLost($e)) {
                $this->reconnect();
                return $this->pdo->query($stament);
            }

            throw new RuntimeException("Query Execution Failed: {$e->getMessage()}", (int) $e->getCode(), $e);
        }
    }

    #[Override]
    public function prepare(string $statement): PDOStatement
    {
        try {
            return $this->getPdo()->prepare($statement);
        } catch (PDOException $e) {
            if ($this->isConnectionLost($e)) {
                $this->reconnect();
                return $this->pdo->prepare($statement);
            }
            throw new RuntimeException("Prepare Statement Failed: {$e->getMessage()}", (int) $e->getCode(), $e);
        }
    }

    #[Override]
    public function transaction(callable $callback): mixed
    {
        $pdo = $this->getPdo();

        if ($pdo->inTransaction()) {
            throw new RuntimeException("Nested transactions are prohibited without savepoints.");
        }

        $pdo->beginTransaction();

        try {
            /** @var mixed $result */
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Call this at the end of each worker cycle (e.g. inside index.php worker loop)
     * to sanitize context for the next request.
     */
    #[Override]
    public function resetState(): void
    {
        if ($this->pdo !== null && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Execute a lightweight health check on the connection.
     */

    private function isAlive(): bool
    {
        if ($this->pdo === null) {
            return false;
        }

        try {
            //Heartbeat query directly agains PostgreSQL socket
            $stmt = $this->pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (PDOException) {
            return false;
        }
    }

    private function reconnect(): void
    {
        $this->pdo = null; // Free old connection handle form RAM.

        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => FALSE,
            // Fast timeout to fail-fast if PostgreSQL goes down.
            PDO::ATTR_TIMEOUT => 3
        ];

        try {
            $this->pdo = new PDO(
                $this->dsn,
                $this->username,
                $this->password,
                $this->options + $defaultOptions
            );
        } catch (PDOException $e) {
            throw new RuntimeException("Worker DB Reconnection Failure: {$e->getMessage()}", (int) $e->getCode(), $e);
        }
    }

    private function isConnectionLost(PDOException $e): bool
    {
        $sqlState = $e->getCode();
        //PostgreSQL connection error codes (08006, 08001, 08004, 57P01).
        return in_array($sqlState, ['08006', '08001', '08004', '57P01'], true)
            || str_contains(strtolower($e->getMessage()), 'server closed the connection unexpectedly. ');
    }
}
