<?php

declare(strict_types=1);

namespace AetherLink\Core\Database\Migrations;

use AetherLink\Core\Database\DatabaseConnectionInterface;
use PDO;

final readonly class MigrationRepository
{
    /**
     * Deterministic 64-bit integer representing the cluster migration lock namespace.
     */

    private const ADVISORY_LOCK_ID = 714238910111213;

    public function __construct(
        private DatabaseConnectionInterface $connection,
        private string $tableName = 'schema_migrations'
    ) {}

    /**
     * Provisions the schema tracking table
     */

    public function ensureTableExists(): void
    {
        $sql = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
            id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INT NOT NULL,
            executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP 
            );',
            $this->tableName
        );
        $this->connection->getPdo()->exec($sql);
    }

    /**
     * Aquires an exclusive, cluster-wide session advisory lock in PostgreSQL.
     */

    public function acquireLock(): void
    {
        $stmt = $this->connection->prepare('SELECT pg_advisory_lock(:id)');
        $stmt->execute(['id' => self::ADVISORY_LOCK_ID]);
    }

    /**
     * Relesase the exclucsive session advisory lock.
     */

    public function releaseLock(): void
    {
        $stmt = $this->connection->prepare('SELECT pg_advisory_unlock(:id)');
        $stmt->execute(['id' => self::ADVISORY_LOCK_ID]);
    }

    /**
     * Fetched all applied migration class name ordered chronologically.
     *
     * @return list<string>
     */
    public function getExecutedMigrations(): array
    {
        $stmt = $this->connection->query(sprintf('SELECT migration FROM %s ORDER BY id ASC', $this->tableName));

        /** @var list<string> */
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * get the next batch sequence number.
     *
     * @return integer
     */
    public function getNextBatchNumber(): int
    {
        $stmt = $this->connection->query(sprintf('SELECT MAX(batch) FROM %s', $this->tableName));

        $maxBatch = $stmt->fetchColumn();

        return ($maxBatch !== false && $maxBatch !== null) ? ((int) $maxBatch) + 1 : 1;
    }

    /**
     * Retrieves all records from the latest migration batch.
     *
     * @return list<string>
     */
    public function getLastBatchMigrations(): array
    {
        $stmt = $this->connection->query(sprintf('SELECT MAX(batch) FROM %s', $this->tableName));

        $lastBatch = $stmt->fetchColumn();
        if ($lastBatch === false || $lastBatch === null) {
            return [];
        }

        $stmt = $this->connection->prepare(sprintf('SELECT migration FROM %s WHERE batch = :batch ORDER BY id DESC', $this->tableName));
        $stmt->execute(['batch' => (int) $lastBatch]);

        /** @var list<string> */
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }


    /**
     * Logs an executed migration into the tracking table.
     */

    public function log(string $migration, int $batch): void
    {
        $stmt = $this->connection->prepare(
            sprintf('INSERT INTO %s (migration, batch) VALUES (:migration, :batch)', $this->tableName)
        );
        $stmt->execute([
            'migration' => $migration,
            'batch'      => $batch,
        ]);
    }

    /**
     * Removes a migration record upon rollback.
     */

    public function delete(string $migration): void
    {
        $stmt = $this->connection->prepare(
            sprintf('DELETE FROM %s WHERE migration = :migration', $this->tableName)
        );

        $stmt->execute([
            'migration' => $migration
        ]);
    }
}
