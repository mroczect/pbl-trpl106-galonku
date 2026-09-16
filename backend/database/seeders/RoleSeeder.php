<?php
require_once __DIR__ . '/Seeder.php';

return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $roles = [
            ['id' => 1, 'name' => 'admin',     'description' => 'Administrator sistem'],
            ['id' => 2, 'name' => 'kurir',     'description' => 'Kurir pengantaran'],
            ['id' => 3, 'name' => 'pelanggan', 'description' => 'Pelanggan depot air'],
        ];

        $stmt = $pdo->prepare(
            "INSERT INTO roles (id, name, description) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE description = VALUES(description)"
        );

        foreach ($roles as $r) {
            $stmt->execute([$r['id'], $r['name'], $r['description']]);
        }
    }
};
