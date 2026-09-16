<?php
namespace App\Models;

use App\Core\{Model, Database};

class Product extends Model
{
    protected static string $table = 'products';
    protected static array $fillable = [
        'sku', 'name', 'category', 'price', 'stock', 'is_active',
    ];

    public static function lowStock(int $threshold = 10): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT * FROM products WHERE stock <= ? AND is_active = 1 ORDER BY stock ASC"
        );
        $stmt->execute([$threshold]);
        return $stmt->fetchAll();
    }

    public static function skuExists(string $sku, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM products WHERE sku = ?";
        $params = [$sku];
        if ($exceptId !== null) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }
        $sql .= " LIMIT 1";

        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public static function reduceStock(int $id, int $qty): bool
    {
        $stmt = Database::connect()->prepare(
            "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?"
        );
        $stmt->execute([$qty, $id, $qty]);
        return $stmt->rowCount() > 0;
    }

    public static function increaseStock(int $id, int $qty): bool
    {
        $stmt = Database::connect()->prepare(
            "UPDATE products SET stock = stock + ? WHERE id = ?"
        );
        return $stmt->execute([$qty, $id]);
    }
}
