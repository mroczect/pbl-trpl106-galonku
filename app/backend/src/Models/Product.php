<?php
namespace App\Models;

use App\Core\{Model, Database};

class Product extends Model
{
    protected static string $table = 'products';

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
