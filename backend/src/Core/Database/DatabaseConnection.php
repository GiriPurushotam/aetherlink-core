<?php

declare(strict_types=1);

namespace AetherLink\Core\Database;

class DatabaseConnection
{
    public function query(string $sql): string
    {
        return "Executing structural query: " . $sql;
    }
}
