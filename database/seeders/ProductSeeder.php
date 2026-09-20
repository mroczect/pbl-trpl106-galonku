<?php
declare(strict_types=1);

use Database\Seeder\Seeder;

return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $products = [
            ['sku' => 'GLN-AQUA-19L',  'name' => 'Galon Aqua 19L',      'category' => 'galon',     'price' => 20000.00,  'stock' => 50,  'is_active' => 1],
            ['sku' => 'GLN-RO-19L',    'name' => 'Galon Isi Ulang 19L', 'category' => 'galon',     'price' => 6000.00,   'stock' => 100, 'is_active' => 1],
            ['sku' => 'GAS-LPG-3KG',   'name' => 'Gas LPG 3kg',         'category' => 'lain',      'price' => 25000.00,  'stock' => 30,  'is_active' => 1],
            ['sku' => 'GAS-LPG-12KG',  'name' => 'Gas LPG 12kg',        'category' => 'lain',      'price' => 180000.00, 'stock' => 15,  'is_active' => 1],
            ['sku' => 'AKS-TUTUP-GLN', 'name' => 'Tutup Galon',         'category' => 'aksesoris', 'price' => 5000.00,   'stock' => 200, 'is_active' => 1],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO products (sku, name, category, price, stock, is_active)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                category = VALUES(category),
                price = VALUES(price)'
        );

        foreach ($products as $p) {
            $stmt->execute([
                $p['sku'], $p['name'], $p['category'],
                $p['price'], $p['stock'], $p['is_active'],
            ]);
        }
    }

    public function priority(): int
    {
        return 30;
    }
};
