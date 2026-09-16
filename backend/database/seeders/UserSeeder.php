<?php
require_once __DIR__ . '/Seeder.php';

return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $users = [
            ['role_id' => 1, 'name' => 'Admin Galonku',  'email' => 'admin@galonku.com', 'phone' => '081234567890', 'password' => 'admin123'],
            ['role_id' => 2, 'name' => 'Kurir Demo',     'email' => 'kurir@galonku.com', 'phone' => '081234567891', 'password' => 'kurir123'],
            ['role_id' => 3, 'name' => 'Pelanggan Demo', 'email' => 'user@galonku.com',  'phone' => '081234567892', 'password' => 'pelanggan123'],
        ];

        $stmt = $pdo->prepare(
            "INSERT INTO users (role_id, name, email, phone, password_hash, is_active)
             VALUES (?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                password_hash = VALUES(password_hash),
                phone = VALUES(phone)"
        );

        foreach ($users as $u) {
            $stmt->execute([
                $u['role_id'], $u['name'], $u['email'], $u['phone'],
                password_hash($u['password'], PASSWORD_BCRYPT),
            ]);
        }
    }
};
