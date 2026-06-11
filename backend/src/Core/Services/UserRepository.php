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

    public function getUserData(int $id): string
    {
        return $this->db->query("SELECT * FROM users WHERE id = " . $id);
    }
}
