<?php
use PHPUnit\Framework\TestCase;
use App\Models\Product;

class ProductTest extends TestCase
{
    public function test_all_returns_array(): void
    {
        $products = Product::all();
        $this->assertIsArray($products);
    }

    public function test_find_returns_array_or_null(): void
    {
        $product = Product::find(1);
        $this->assertTrue($product === null || is_array($product));
    }
}
