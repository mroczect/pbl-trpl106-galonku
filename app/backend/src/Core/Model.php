<?php
namespace App\Core;

abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';

    public static function all(string $orderBy = 'id DESC'): array
    {
        $stmt = Database::connect()->query(
            "SELECT * FROM " . static::$table . " ORDER BY $orderBy"
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function first(string $column, mixed $value): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT * FROM " . static::$table . " WHERE $column = ? LIMIT 1"
        );
        $stmt->execute([$value]);
        return $stmt->fetch() ?: null;
    }

    public static function where(string $column, mixed $value): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT * FROM " . static::$table . " WHERE $column = ?"
        );
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $ph   = implode(', ', array_fill(0, count($data), '?'));
        $stmt = Database::connect()->prepare(
            "INSERT INTO " . static::$table . " ($cols) VALUES ($ph)"
        );
        $stmt->execute(array_values($data));
        return (int) Database::connect()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        $stmt = Database::connect()->prepare(
            "UPDATE " . static::$table . " SET $set WHERE " . static::$primaryKey . " = ?"
        );
        return $stmt->execute([...array_values($data), $id]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connect()->prepare(
            "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?"
        );
        return $stmt->execute([$id]);
    }
}
