<?php
declare(strict_types=1);

use App\Core\Database;

class Migrator
{
    private PDO $pdo;
    private string $migrationPath;
    private string $seederPath;

    /**
     * Urutan seeder sesuai dependency (bukan alfabetis).
     * RoleSeeder -> UserSeeder -> ProductSeeder -> CustomerSeeder -> DemoSeeder
     */
    private const SEEDER_ORDER = [
        'RoleSeeder',
        'UserSeeder',
        'ProductSeeder',
        'CustomerSeeder',
        'DemoSeeder',
    ];

    public function __construct(string $migrationPath, string $seederPath)
    {
        $this->migrationPath = $migrationPath;
        $this->seederPath    = $seederPath;
        $this->pdo           = $this->connectServer();
        $this->ensureDatabase();
        $this->pdo = Database::connect();
        $this->ensureMigrationsTable();
    }

    private function connectServer(): PDO
    {
        $config = require dirname(__DIR__) . '/config/database.php';
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";

        try {
            return new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            fwrite(STDERR, "Cannot connect to MySQL: " . $e->getMessage() . "\n");
            fwrite(STDERR, "Check MySQL is running and DB_USERNAME/DB_PASSWORD in .env\n");
            exit(1);
        }
    }

    private function ensureDatabase(): void
    {
        $name = $_ENV['DB_DATABASE'];
        $this->pdo->exec(
            "CREATE DATABASE IF NOT EXISTS `$name`
             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration`   VARCHAR(255) NOT NULL,
                `batch`       INT UNSIGNED NOT NULL,
                `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_migration` (`migration`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // ==================== MIGRATE ====================

    public function migrate(): void
    {
        $ran     = $this->ranMigrations();
        $pending = array_diff($this->availableMigrations(), $ran);

        if (empty($pending)) {
            echo "No pending migrations.\n";
            return;
        }

        $batch = $this->nextBatch();
        echo "Batch $batch: " . count($pending) . " migration(s)\n";

        foreach ($pending as $name) {
            $this->runMigration($name, $batch);
        }

        echo "Migration complete.\n";
    }

    /**
     * MySQL/MariaDB tidak mendukung transactional DDL.
     * Setiap CREATE/ALTER/DROP TABLE otomatis commit transaksi.
     * Jadi beginTransaction() tidak dipakai di sini.
     */
    private function runMigration(string $name, int $batch): void
    {
        echo "  $name ... ";

        $file = $this->migrationPath . '/' . $name . '.php';
        if (!file_exists($file)) {
            echo "FAIL (file not found)\n";
            return;
        }

        $instance = require $file;

        if (!is_object($instance) || !method_exists($instance, 'up')) {
            echo "FAIL (no up method)\n";
            return;
        }

        try {
            $instance->up($this->pdo);

            $stmt = $this->pdo->prepare(
                "INSERT INTO migrations (migration, batch) VALUES (?, ?)"
            );
            $stmt->execute([$name, $batch]);

            echo "done\n";
        } catch (\Throwable $e) {
            echo "FAIL\n";
            throw $e;
        }
    }

    // ==================== ROLLBACK ====================

    public function rollback(int $steps = 1): void
    {
        $batches = $this->pdo->query(
            "SELECT DISTINCT batch FROM migrations ORDER BY batch DESC LIMIT $steps"
        )->fetchAll(PDO::FETCH_COLUMN);

        if (empty($batches)) {
            echo "Nothing to roll back.\n";
            return;
        }

        foreach ($batches as $batch) {
            echo "Rollback batch $batch\n";

            $stmt = $this->pdo->prepare(
                "SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC"
            );
            $stmt->execute([$batch]);
            $names = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($names as $name) {
                $file = $this->migrationPath . '/' . $name . '.php';
                if (!file_exists($file)) {
                    echo "  skip $name (file missing)\n";
                    continue;
                }

                echo "  $name ... ";
                $instance = require $file;

                if (!method_exists($instance, 'down')) {
                    echo "skip (no down)\n";
                    continue;
                }

                try {
                    $instance->down($this->pdo);
                    $this->pdo->prepare("DELETE FROM migrations WHERE migration = ?")
                               ->execute([$name]);
                    echo "done\n";
                } catch (\Throwable $e) {
                    echo "FAIL\n";
                    throw $e;
                }
            }
        }

        echo "Rollback complete.\n";
    }

    // ==================== FRESH / RESET ====================

    public function fresh(bool $withSeed = false): void
    {
        echo "Dropping all tables.\n";
        $this->dropAllTables();
        $this->pdo->exec("DROP TABLE IF EXISTS migrations");
        $this->ensureMigrationsTable();

        $this->migrate();

        if ($withSeed) {
            $this->seed();
        }
    }

    public function reset(bool $withSeed = false): void
    {
        $count = (int) $this->pdo->query(
            "SELECT COUNT(DISTINCT batch) FROM migrations"
        )->fetchColumn();

        if ($count > 0) {
            $this->rollback($count);
        }

        $this->migrate();

        if ($withSeed) {
            $this->seed();
        }
    }

    private function dropAllTables(): void
    {
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "  drop $table\n";
        }
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function dropDatabase(): void
    {
        $name   = $_ENV['DB_DATABASE'];
        $config = require dirname(__DIR__) . '/config/database.php';
        $dsn    = "mysql:host={$config['host']};port={$config['port']}";

        $server = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $server->exec("DROP DATABASE IF EXISTS `$name`");
        echo "Database $name dropped.\n";
    }

    // ==================== SEED ====================

    public function seed(?string $only = null): void
    {
        $files = $this->seederFiles();   // sudah urut dependency

        // Map name => path
        $map = [];
        foreach ($files as $file) {
            $map[pathinfo($file, PATHINFO_FILENAME)] = $file;
        }

        if ($only !== null) {
            if (!isset($map[$only])) {
                fwrite(STDERR, "Seeder not found: $only\n");
                fwrite(STDERR, "Available: " . implode(', ', array_keys($map)) . "\n");
                return;
            }
            $map = [$only => $map[$only]];
        }

        echo "Running " . count($map) . " seeder(s).\n";

        $failed = 0;
        foreach ($map as $name => $file) {
            echo "  $name ... ";

            try {
                $instance = require $file;
                if (!method_exists($instance, 'run')) {
                    echo "skip (no run)\n";
                    continue;
                }
                $instance->run($this->pdo);
                echo "done\n";
            } catch (\Throwable $e) {
                $failed++;
                echo "FAIL\n";
                fwrite(STDERR, "    " . $e->getMessage() . "\n");
            }
        }

        if ($failed === 0) {
            echo "Seeding complete.\n";
        } else {
            echo "Seeding complete with $failed failure(s).\n";
        }
    }

    // ==================== STATUS ====================

    public function status(): void
    {
        $ran = $this->ranMigrations();
        $all = $this->availableMigrations();

        echo "Migrations:\n";
        echo str_repeat('-', 60) . "\n";

        foreach ($all as $name) {
            $isRan  = in_array($name, $ran, true);
            $status = $isRan ? 'RAN' : 'PENDING';
            echo sprintf("  [%-7s] %s\n", $status, $name);
        }

        $done = count(array_intersect($ran, $all));
        echo sprintf("\nTotal: %d/%d\n", $done, count($all));

        echo "\nSeeders:\n";
        echo str_repeat('-', 60) . "\n";
        foreach ($this->seederFiles() as $file) {
            echo "  " . pathinfo($file, PATHINFO_FILENAME) . "\n";
        }
    }

    // ==================== HELPERS ====================

    private function availableMigrations(): array
    {
        $files = glob($this->migrationPath . '/*.php');
        $names = [];

        foreach ($files as $f) {
            $base = pathinfo($f, PATHINFO_FILENAME);
            if (str_starts_with($base, '_')) continue;
            $names[] = $base;
        }

        sort($names);
        return $names;
    }

    private function ranMigrations(): array
    {
        return $this->pdo->query("SELECT migration FROM migrations ORDER BY id")
                         ->fetchAll(PDO::FETCH_COLUMN);
    }

    private function nextBatch(): int
    {
        return 1 + (int) $this->pdo->query(
            "SELECT COALESCE(MAX(batch), 0) FROM migrations"
        )->fetchColumn();
    }

    /**
     * Return seeder files SESUAI DEPENDENCY ORDER (bukan alfabetis).
     */
    private function seederFiles(): array
    {
        $files = [];

        // 1. Ambil sesuai urutan yang didefinisikan
        foreach (self::SEEDER_ORDER as $name) {
            $path = $this->seederPath . '/' . $name . '.php';
            if (file_exists($path)) {
                $files[] = $path;
            }
        }

        // 2. Fallback: seeder yang tidak terdaftar, taruh di akhir
        foreach (glob($this->seederPath . '/*Seeder.php') as $f) {
            $base = pathinfo($f, PATHINFO_FILENAME);
            if ($base === 'Seeder') continue;
            if (in_array($f, $files, true)) continue;
            $files[] = $f;
        }

        return $files;
    }
}
