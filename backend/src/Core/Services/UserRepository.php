<?php

declare(strict_types=1);

namespace AetherLink\Core\Services;

use AetherLink\Core\Database\DatabaseConnection;

class UserRepository
{
    // The Container must read this typehint and inject DatabaseConnection automatically.
    public function __construct(
        private DatabaseConnection $db
    ) {}

    public function getSystemHealth(): array
    {
        $result = $this->db->query("SELECT version(), current_database() as database, now() as server_time;");
        return $result[0] ?? [];
    }
}
