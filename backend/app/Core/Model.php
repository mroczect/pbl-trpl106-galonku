<?php
namespace App\Core;

abstract class Model
{
    protected static string $table;
    protected static string $primaryKey = 'id';
    protected static array $fillable = [];

    public static function table(): string { return static::$table; }

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
        static::assertColumn($column);
        $stmt = Database::connect()->prepare(
            "SELECT * FROM " . static::$table . " WHERE $column = ? LIMIT 1"
        );
        $stmt->execute([$value]);
        return $stmt->fetch() ?: null;
    }

    public static function where(string $column, mixed $value): array
    {
        static::assertColumn($column);
        $stmt = Database::connect()->prepare(
            "SELECT * FROM " . static::$table . " WHERE $column = ?"
        );
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $data = static::filterFillable($data);

        if (empty($data)) throw new \InvalidArgumentException('Empty data');

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
        $data = static::filterFillable($data);

        if (empty($data)) return false;

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

    public static function paginate(int $page = 1, int $perPage = 15, array $filters = [], string $orderBy = 'id DESC'): array
    {
        $page    = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $offset  = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        foreach ($filters as $col => $val) {
            static::assertColumn($col);
            if ($val !== null && $val !== '') {
                $where[] = "$col = ?";
                $params[] = $val;
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $table = static::$table;

        $countStmt = Database::connect()->prepare("SELECT COUNT(*) FROM $table $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT * FROM $table $whereSql ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);

        return [
            'data'        => $stmt->fetchAll(),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    protected static function filterFillable(array $data): array
    {
        if (empty(static::$fillable)) return $data;
        return array_intersect_key($data, array_flip(static::$fillable));
    }

    protected static function assertColumn(string $column): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            throw new \InvalidArgumentException('Invalid column name');
        }
    }
}
