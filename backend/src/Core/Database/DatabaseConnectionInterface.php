<?php

declare(strict_types=1);

namespace AetherLink\Core\Database;

use PDO;
use PDOStatement;

interface DatabaseConnectionInterface
{
    /**
     * Ontains an active, healthy PDO instance.
     */

    public function getPdo(): PDO;

    /**
     * Execute a raw query with worker-safe error handling.
     */

    public function query(string $statement): PDOStatement;

    /**
     * Prepare a statement safely.
     */

    public function prepare(string $statement): PDOStatement;

    /**
     * Atomic transaction execution wrapper. Automatically rolls back on exception.
     * @template T
     * @param callable(PDO): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;

    /**
     * Worker teardown hook to clean up uncomitted transaction and state.
     */

    public function resetState(): void;
}
