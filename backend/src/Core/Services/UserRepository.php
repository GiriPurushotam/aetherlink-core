<?php

declare(strict_types=1);

namespace AetherLink\Core\Services;

use AetherLink\Core\Database\DatabaseConnectionInterface;

class UserRepository
{
    // The Container must read this typehint and inject DatabaseConnection automatically.
    public function __construct(
        private DatabaseConnectionInterface $db
    ) {}

    public function getSystemHealth(): array
    {
        $result = $this->db->query("SELECT version(), current_database() as database, (now() AT TIME ZONE 'Asia/Kathmandu')::timestamp(0) as server_time;");
        $row = $result->fetch();
        return $row !== false ? $row : [];
    }
}
