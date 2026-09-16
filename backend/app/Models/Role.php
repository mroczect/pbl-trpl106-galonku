<?php
namespace App\Models;

use App\Core\Database;

class Role
{
    public static function all(): array
    {
        return Database::connect()
            ->query("SELECT * FROM roles ORDER BY id ASC")
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare("SELECT * FROM roles WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByName(string $name): ?array
    {
        $stmt = Database::connect()->prepare("SELECT * FROM roles WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        return $stmt->fetch() ?: null;
    }
}
