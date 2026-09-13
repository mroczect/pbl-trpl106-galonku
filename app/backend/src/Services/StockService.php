<?php
namespace App\Services;

use App\Models\Product;

class StockService
{
    public static function reduce(int $productId, int $qty): bool
    {
        return Product::reduceStock($productId, $qty);
    }

    public static function restock(int $productId, int $qty): bool
    {
        return Product::increaseStock($productId, $qty);
    }
}
