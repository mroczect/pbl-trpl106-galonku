<?php
declare(strict_types=1);

use Database\Seeder\Seeder;

return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $customers = [
            ['name' => 'Ibu Siti',  'phone' => '081111222333', 'address' => 'Jl. Merdeka No. 1',    'is_active' => 1],
            ['name' => 'Pak Budi',  'phone' => '082222333444', 'address' => 'Jl. Sudirman No. 12',  'is_active' => 1],
            ['name' => 'Toko Jaya', 'phone' => '083333444555', 'address' => 'Jl. Pasar Baru No. 5', 'is_active' => 1],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO customers (name, phone, address, is_active)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                address = VALUES(address)'
        );

        foreach ($customers as $c) {
            $stmt->execute([$c['name'], $c['phone'], $c['address'], $c['is_active']]);
        }
    }

    public function priority(): int
    {
        return 40;
    }
};
