<?php

abstract class Migration
{
    abstract public function up(PDO $pdo): void;

    public function down(PDO $pdo): void
    {
    }

    protected function table(PDO $pdo, string $name, callable $callback): void
    {
        if ($this->hasTable($pdo, $name)) {
            echo "(skip) ";
            return;
        }
        $pdo->exec($callback());
    }

    protected function hasTable(PDO $pdo, string $name): bool
    {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($name));
        return (bool) $stmt->fetchColumn();
    }
}
