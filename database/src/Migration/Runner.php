<?php
declare(strict_types=1);

namespace Database\Migration;

use Database\Support\{Guard, Output};
use PDO;

final class Runner
{
    private string $path;
    private const LOCK_NAME = 'galonku_migrator';
    private const LOCK_TIMEOUT = 5;

    public function __construct(
        string $migrationPath,
        private Repository $repository,
        private PDO $pdo,
    ) {
        Guard::assertDirExists($migrationPath, 'Migration');
        $this->path = rtrim($migrationPath, '/');
    }

    public function available(): array
    {
        $files = glob("{$this->path}/*.php") ?: [];
        $names = [];

        foreach ($files as $f) {
            $base = pathinfo($f, PATHINFO_FILENAME);
            if (str_starts_with($base, '_')) {
                continue;
            }
            $names[] = $base;
        }

        sort($names);
        return $names;
    }

    public function pending(): array
    {
        return array_values(array_diff($this->available(), $this->repository->ran()));
    }

    public function runPending(): int
    {
        $this->acquireLock();
        $pending = $this->pending();
        if (empty($pending)) {
            Output::info('No pending migrations.');
            return 0;
        }

        $batch = $this->repository->nextBatch();
        Output::info('Batch ' . $batch . ': ' . count($pending) . ' migration(s)');

        foreach ($pending as $name) {
            $this->runOne($name, $batch);
        }
        return count($pending);
    }

    public function rollback(int $steps = 1): int
    {
        $this->acquireLock();
        $batches = $this->repository->batchesDescending($steps);
        if (empty($batches)) {
            Output::info('Nothing to roll back.');
            return 0;
        }

        $total = 0;
        foreach ($batches as $batch) {
            Output::info("Rolling back batch {$batch}");
            foreach ($this->repository->namesInBatch($batch) as $name) {
                if ($this->rollbackOne($name)) {
                    $total++;
                }
            }
        }
        return $total;
    }

    private function acquireLock(): void
    {
        $stmt = $this->pdo->prepare('SELECT GET_LOCK(?, ?)');
        $stmt->execute([self::LOCK_NAME, self::LOCK_TIMEOUT]);
        $result = $stmt->fetchColumn();
        if ((string) $result !== '1') {
            throw new \RuntimeException(
                'Another migration/seeder process is running. Try again later.'
            );
        }
    }

    private function runOne(string $name, int $batch): void
    {
        Output::progress($name);
        $migration = $this->load($name);

        if ($migration === null) {
            Output::skip('file missing');
            return;
        }

        try {
            $this->pdo->beginTransaction();
            $migration->up($this->pdo);
            $this->repository->record($name, $batch);
            
            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            Output::fail('failed');
            throw $e;
        }
        Output::done();
    }

    private function rollbackOne(string $name): bool
    {
        Output::progress($name);
        $migration = $this->load($name);

        if ($migration === null) {
            Output::skip('file missing');
            return false;
        }

        try {
            $this->pdo->beginTransaction();
            $migration->down($this->pdo);
            $this->repository->forget($name);
            
            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            Output::fail('failed');
            throw $e;
        }
        Output::done();
        return true;
    }

    private function load(string $name): ?Migration
    {
        $file = "{$this->path}/{$name}.php";
        if (!is_file($file)) {
            return null;
        }

        $instance = require $file;
        if (!$instance instanceof Migration) {
            throw new \RuntimeException(
                "Migration {$name} must return a Database\\Migration\\Migration instance"
            );
        }
        return $instance;
    }
}
