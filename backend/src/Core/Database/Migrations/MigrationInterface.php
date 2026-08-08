<?php

declare(strict_types=1);

namespace AetherLink\Core\Database\Migrations;

use AetherLink\Core\Database\DatabaseConnectionInterface;

interface MigrationInterface
{
    /**
     * Determines whether this migration runs inside an explicit transaction block.
     * Must return false for non-transactional DDL(e.g. CREATE INDEX CUNCURRENTLY).
     */

    public function isTransactional(): bool;

    /**
     * Execute forward DDL transformations.
     */

    public function up(DatabaseConnectionInterface $connection): void;

    /**
     * Rollback DDL transformations.
     */

    public function down(DatabaseConnectionInterface $connection): void;
}
