<?php
declare(strict_types=1);

use Database\Seeder\Seeder;

return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $roles = [
            ['id' => 1, 'name' => 'administrator', 'description' => 'System administrator with full access'],
            ['id' => 2, 'name' => 'agent',         'description' => 'Field agent handling deliveries'],
            ['id' => 3, 'name' => 'customer',      'description' => 'Registered customer account'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO roles (id, name, description) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE description = VALUES(description)'
        );

        foreach ($roles as $r) {
            $stmt->execute([$r['id'], $r['name'], $r['description']]);
        }
    }

    public function priority(): int
    {
        return 10;
    }
};
