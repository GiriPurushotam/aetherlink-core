<?php

declare(strict_types=1);

namespace AetherLink\Core\Database\Migrations;

use AetherLink\Core\Database\DatabaseConnectionInterface;
use InvalidArgumentException;
use PDO;
use RuntimeException;

final readonly class MigrationManager
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private MigrationRepository $repository,
        private string $migrationsPath
    ) {
        if (!is_dir($this->migrationsPath)) {
            throw new InvalidArgumentException(sprintf('Migrations directoy does not exist: %s', $this->migrationsPath));
        }
    }


    /**
     * Runs all pending migrations.
     *
     * @param (callable(string): void)|null $outputCallback
     * @return list<string>
     */
    public function migrate(?callable $outputCallback = null): array
    {
        $this->repository->ensureTableExists();
        $this->repository->acquireLock();

        $applied = [];

        try {
            $executed = $this->repository->getExecutedMigrations();
            $files = $this->getMigrationFiles();
            $batch = $this->repository->getNextBatchNumber();

            foreach ($files as $file) {
                $className = $this->resolveClassName($file);

                if (in_array($className, $executed, true)) {
                    continue;
                }

                $migration = $this->instantiateMigration($file, $className);

                if ($outputCallback !== null) {
                    $outputCallback(sprintf('Migrating: %s', $className));
                }

                $this->executeUp($migration, $className, $batch);

                if ($outputCallback !== null) {
                    $outputCallback(sprintf('Migrated: %s', $className));
                }

                $applied[] = $className;
            }
        } finally {
            $this->repository->releaseLock();
        }

        return $applied;
    }

    /**
     * Rolls back the last batch of applied migrations.
     *
     * @param (callable(string): void)|null $outputCallback
     * @return list<string>
     */
    public function rollback(?callable $outputCallback = null): array
    {
        $this->repository->ensureTableExists();
        $this->repository->acquireLock();

        $rolledBack = [];

        try {
            $lastBatchMigrations = $this->repository->getLastBatchMigrations();
            if (empty($lastBatchMigrations)) {
                return [];
            }

            foreach ($lastBatchMigrations as $className) {
                $file = $this->findFileForClass($className);
                $migration = $this->instantiateMigration($file, $className);
                if ($outputCallback !== null) {
                    $outputCallback(sprintf('Rolling Back: %s', $className));
                }

                $this->executeDown($migration, $className);

                if ($outputCallback !== null) {
                    $outputCallback(sprintf('Rolled Back: %s', $className));
                }

                $rolledBack[] = $className;
            }
        } finally {
            $this->repository->releaseLock();
        }

        return $rolledBack;
    }

    private function executeUp(MigrationInterface $migration, string $className, int $batch): void
    {
        if ($migration->isTransactional()) {
            $this->connection->transaction(function (PDO $pdo) use ($migration, $className, $batch): void {
                $migration->up($this->connection);
                $this->repository->log($className, $batch);
            });
        } else {
            $migration->up($this->connection);
            $this->repository->log($className, $batch);
        }
    }


    private function executeDown(MigrationInterface $migration, string $className): void
    {
        if ($migration->isTransactional()) {
            $this->connection->transaction(function (PDO $pdo) use ($migration, $className): void {
                $migration->down($this->connection);
                $this->repository->delete($className);
            });
        } else {
            $migration->down($this->connection);
            $this->repository->delete($className);
        }
    }

    private function getMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/Version*.php');

        if ($files === false) {
            return [];
        }

        sort($files);

        return $files;
    }

    private function resolveClassName(string $filePath): string
    {
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        return sprintf('AetherLink\\Dataabase\\Migrations\\%s', $filename);
    }

    private function instantiateMigration(string $filePath, string $className): MigrationInterface
    {
        if (!class_exists($className, false)) {
            require_once $filePath;
        }

        if (!class_exists($className, false)) {
            throw new RuntimeException(sprintf('Migration class "%s" was not found in file "%s".', $className, $file));
        }

        $instance = new $className();
        if (!$instance instanceof MigrationInterface) {
            throw new RuntimeException(sprintf('Migration class "%s" must implement %s.', $className, MigrationInterface::class));
        }

        return $instance;
    }


    private function findFileForClass(string $className): string
    {
        $parts = explode('\\', $className);
        $shortName = end($parts);
        $filePath = sprintf('%s/%s.php', $this->migrationsPath, $shortName);

        if (!file_exists($filePath)) {
            throw new RuntimeException(sprintf('Migration file missing for class "%s" at "%s"', $className, $filePath));
        }

        return $filePath;
    }
}
