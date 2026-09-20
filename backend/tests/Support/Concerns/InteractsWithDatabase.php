<?php
declare(strict_types=1);

namespace Tests\Support\Concerns;

use App\Core\Database;
use PHPUnit\Framework\Assert;

trait InteractsWithDatabase
{
    protected static bool $migrated = false;

    protected function refreshDatabase(): void
    {
        Database::reset();
        $this->clearRateLimitCache();

        if (!self::$migrated) {
            $root = dirname(__DIR__, 4);
            require_once $root . '/database/bootstrap.php';

            $migrator = new \Database\Migrator(
                $root . '/database/migrations',
                $root . '/database/seeders'
            );

            ob_start();
            try {
                $migrator->fresh(true);
            } finally {
                ob_end_clean();
            }
            self::$migrated = true;
        }

        Database::connect()->beginTransaction();
    }

    protected function tearDownDatabase(): void
    {
        $pdo = Database::connect();
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); 
        }
    }

    protected function clearRateLimitCache(): void
    {
        $dir = dirname(__DIR__, 3) . '/storage/cache';
        if (!is_dir($dir)) return;

        foreach (glob($dir . '/rl_*.json') as $f) {
            @unlink($f);
        }
    }

    protected function assertDatabaseHas(string $table, array $conditions): void
    {
        $row = $this->fetchFirst($table, $conditions);
        Assert::assertNotNull(
            $row,
            "No row in `$table` matching: " . json_encode($conditions)
        );
    }

    protected function assertDatabaseMissing(string $table, array $conditions): void
    {
        $row = $this->fetchFirst($table, $conditions);
        Assert::assertNull(
            $row,
            "Found unexpected row in `$table` matching: " . json_encode($conditions)
        );
    }

    protected function fetchFirst(string $table, array $conditions): ?array
    {
        $where  = [];
        $params = [];

        foreach ($conditions as $col => $val) {
            $where[]  = "`$col` = ?";
            $params[] = $val;
        }

        $sql = "SELECT * FROM `$table` WHERE " . implode(' AND ', $where) . ' LIMIT 1';
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }
}
