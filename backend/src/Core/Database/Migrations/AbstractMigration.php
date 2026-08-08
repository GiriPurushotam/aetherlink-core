<?php

declare(strict_types=1);

namespace AetherLink\Core\Database\Migrations;

use AetherLink\Core\Database\DatabaseConnectionInterface;

abstract class AbstractMigration implements MigrationInterface
{
    public bool $transactional = true;

    public function isTransactional(): bool
    {
        return $this->transactional;
    }

    abstract public function up(DatabaseConnectionInterface $connection): void;
    abstract public function down(DatabaseConnectionInterface $connection): void;
}
