<?php

declare(strict_types=1);

namespace AetherLink\Core\Database\Migrations\Files;

use AetherLink\Core\Database\DatabaseConnectionInterface;
use AetherLink\Core\Database\Migrations\AbstractMigration;

final class Version20260812102217_create_users_table extends AbstractMigration
{
    public function up(DatabaseConnectionInterface $connection): void
    {
        $sql = <<<SQL
       CREATE TABLE IF NOT EXISTS users(
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
       );
       SQL;
        $connection->getPdo()->exec($sql);
    }

    public function down(DatabaseConnectionInterface $connection): void
    {
        $connection->getPdo()->exec("DROP TABLE IF EXISTS users CASCADE;");
    }
}
