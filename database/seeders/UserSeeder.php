<?php
declare(strict_types=1);

use Database\Seeder\Seeder;

return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $env = getenv('APP_ENV') ?: 'local';
        $isProd = $env === 'production';

        $users = [
            ['role_id' => 1, 'name' => 'Administrator', 'email' => 'admin@galonku.com',    'phone' => '081234567890', 'password' => 'admin123'],
            ['role_id' => 2, 'name' => 'Agent Demo',    'email' => 'agent@galonku.com',    'phone' => '081234567891', 'password' => 'agent123'],
            ['role_id' => 3, 'name' => 'Customer Demo', 'email' => 'customer@galonku.com', 'phone' => '081234567892', 'password' => 'customer123'],
        ];

        if ($isProd) {
            $admin = getenv('SEED_ADMIN_PASSWORD');
            if ($admin === false || $admin === '') {
                throw new \RuntimeException(
                    'Refusing to seed users in production. '
                    . 'Set SEED_ADMIN_PASSWORD (and optionally SEED_AGENT_PASSWORD / SEED_CUSTOMER_PASSWORD).'
                );
            }
            $users[0]['password'] = $admin;
            $users[1]['password'] = getenv('SEED_AGENT_PASSWORD')    ?: $users[1]['password'];
            $users[2]['password'] = getenv('SEED_CUSTOMER_PASSWORD') ?: $users[2]['password'];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO users (role_id, name, email, phone, password_hash, is_active)
             VALUES (?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                phone = VALUES(phone),
                is_active = 1'
        );

        foreach ($users as $u) {
            $stmt->execute([
                $u['role_id'], $u['name'], $u['email'], $u['phone'],
                password_hash($u['password'], PASSWORD_BCRYPT),
            ]);
        }
    }

    public function priority(): int
    {
        return 20;
    }
};
