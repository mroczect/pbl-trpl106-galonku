<?php
declare(strict_types=1);

namespace Tests\Support\Concerns;

use App\Core\Database;
use PHPUnit\Framework\Assert;

trait InteractsWithDatabase
{
    protected function refreshDatabase(): void
    {
        Database::reset();

        $this->clearRateLimitCache();

        require_once dirname(__DIR__, 4) . '/database/Migrator.php';

        $migrator = new \Migrator(
            dirname(__DIR__, 4) . '/database/migrations',
            dirname(__DIR__, 4) . '/database/seeders'
        );

        ob_start();
        try {
            $migrator->fresh(true);
        } finally {
            ob_end_clean();
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
