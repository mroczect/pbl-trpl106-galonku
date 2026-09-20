<?php
declare(strict_types=1);

namespace Database\Migration;

use Database\Support\Guard;
use PDO;

abstract class Migration
{
    abstract public function up(PDO $pdo): void;

    public function down(PDO $pdo): void
    {
        throw new \LogicException(static::class . ' does not implement down()');
    }

    protected function createTable(PDO $pdo, string $table, callable $ddl): void
    {
        if ($this->hasTable($pdo, $table)) {
            return;
        }
        $pdo->exec($ddl());
    }

    protected function dropTable(PDO $pdo, string $table): void
    {
        $pdo->exec('DROP TABLE IF EXISTS ' . Guard::quoteIdentifier($table));
    }

    protected function hasTable(PDO $pdo, string $table): bool
    {
        Guard::assertSafeIdentifier($table);
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?
             LIMIT 1'
        );
        $stmt->execute([$table]);
        return $stmt->fetchColumn() !== false;
    }
}
