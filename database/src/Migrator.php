<?php
declare(strict_types=1);

namespace Database;

use Database\Migration\{Repository as MigrationRepository, Runner as MigrationRunner};
use Database\Seeder\{Repository as SeederRepository, Runner as SeederRunner};
use Database\Support\{Guard, Output};
use PDO;

final class Migrator
{
    private PDO $pdo;
    private MigrationRepository $migrationRepo;
    private MigrationRunner $migrationRunner;
    private SeederRepository $seederRepo;
    private SeederRunner $seederRunner;

    public function __construct(string $migrationPath, string $seederPath)
    {
        $this->ensureDatabase();
        $this->pdo = Connection::database();

        $this->migrationRepo = new MigrationRepository($this->pdo);
        $this->migrationRepo->ensureTable();

        $this->migrationRunner = new MigrationRunner(
            $migrationPath, $this->migrationRepo, $this->pdo
        );

        $this->seederRepo   = new SeederRepository($seederPath);
        $this->seederRunner = new SeederRunner($this->seederRepo, $this->pdo);
    }

    public function migrate(): int
    {
        $this->migrationRunner->runPending();
        Output::ok('Migration complete.');
        return 0;
    }

    public function rollback(int $steps = 1): int
    {
        $this->migrationRunner->rollback($steps);
        Output::ok('Rollback complete.');
        return 0;
    }

    public function fresh(bool $seed = false): int
    {
        Output::info('Dropping all tables.');
        $this->dropAllTables();

        $this->migrationRepo->ensureTable();
        $this->migrationRunner->runPending();

        $exit = 0;
        if ($seed) {
            $exit = $this->seed();
        }
        Output::ok('Database refreshed.');
        return $exit;
    }

    public function reset(bool $seed = false): int
    {
        $batches = $this->migrationRepo->countBatches();
        if ($batches > 0) {
            $this->migrationRunner->rollback($batches);
        }

        $this->migrationRunner->runPending();
        $exit = 0;
        if ($seed) {
            $exit = $this->seed();
        }
        Output::ok('Database reset.');
        return $exit;
    }

    public function seed(?string $only = null): int
    {
        $ran = $this->seederRunner->run($only);
        if ($ran <= 0) {
            Output::fail('No seeders were executed.');
            return 1;
        }
        Output::ok('Seeding complete.');
        return 0;
    }

    public function status(): int
    {
        $ran = $this->migrationRepo->ran();
        $all = $this->migrationRunner->available();

        Output::heading('Migrations');
        Output::line(str_repeat('─', 62));

        foreach ($all as $name) {
            $isRan = in_array($name, $ran, true);
            $label = $isRan
                ? Output::color('[RAN    ]', 'green')
                : Output::color('[PENDING]', 'yellow');
            Output::line("  {$label} {$name}");
        }

        $done = count(array_intersect($ran, $all));
        Output::line('');
        Output::line("  Total: {$done}/" . count($all));

        Output::heading('Seeders');
        Output::line(str_repeat('─', 62));
        foreach (array_keys($this->seederRepo->all()) as $name) {
            Output::line("  {$name}");
        }
        return 0;
    }

    public function dropDatabase(): int
    {
        $db = $this->resolveDbName();
        Guard::assertSafeDatabaseName($db);

        Connection::server()->exec("DROP DATABASE IF EXISTS `{$db}`");
        Output::ok("Database `{$db}` dropped.");
        return 0;
    }

    private function ensureDatabase(): void
    {
        $db = $this->resolveDbName();
        Guard::assertSafeDatabaseName($db);

        Connection::server()->exec(
            "CREATE DATABASE IF NOT EXISTS `{$db}`
             CHARACTER SET utf8mb4
             COLLATE utf8mb4_unicode_ci"
        );
    }

    private function resolveDbName(): string
    {
        $db = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE');
        if (!is_string($db) || $db === '') {
            $db = Connection::config()['database'] ?? 'galonku_db';
        }
        return $db;
    }

    private function dropAllTables(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        try {
            $stmt = $this->pdo->query(
                "SELECT table_name FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'"
            );
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $safe = Guard::quoteIdentifier($table);
                $this->pdo->exec("DROP TABLE IF EXISTS {$safe}");
                Output::line("  dropped {$table}");
            }
        } finally {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
}
