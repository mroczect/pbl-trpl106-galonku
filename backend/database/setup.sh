#!/bin/bash
set -e

BASE="$(cd "$(dirname "$0")" && pwd)"

mkdir -p "$BASE/migrations"
mkdir -p "$BASE/seeders"

# ================= MIGRATIONS =================

cat > "$BASE/migrations/2024_01_01_000001_create_roles_table.php" <<'PHP'
<?php
require_once __DIR__ . '/_Base.php';

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->table($pdo, 'roles', fn() => "
            CREATE TABLE `roles` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`        VARCHAR(50) NOT NULL,
                `description` VARCHAR(255) DEFAULT NULL,
                `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_roles_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `roles`");
    }
};
PHP

cat > "$BASE/migrations/2024_01_01_000002_create_users_table.php" <<'PHP'
<?php
require_once __DIR__ . '/_Base.php';

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->table($pdo, 'users', fn() => "
            CREATE TABLE `users` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `role_id`        INT UNSIGNED NOT NULL,
                `name`           VARCHAR(100) NOT NULL,
                `email`          VARCHAR(150) NOT NULL,
                `phone`          VARCHAR(20) DEFAULT NULL,
                `password_hash`  VARCHAR(255) NOT NULL,
                `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
                `last_login_at`  TIMESTAMP NULL DEFAULT NULL,
                `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_users_email` (`email`),
                KEY `idx_users_role` (`role_id`),
                KEY `idx_users_active` (`is_active`),
                CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`)
                    REFERENCES `roles`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `users`");
    }
};
PHP

cat > "$BASE/migrations/2024_01_01_000003_create_customers_table.php" <<'PHP'
<?php
require_once __DIR__ . '/_Base.php';

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->table($pdo, 'customers', fn() => "
            CREATE TABLE `customers` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(100) NOT NULL,
                `phone`      VARCHAR(20) NOT NULL,
                `address`    TEXT DEFAULT NULL,
                `notes`      TEXT DEFAULT NULL,
                `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_customers_phone` (`phone`),
                KEY `idx_customers_active` (`is_active`),
                KEY `idx_customers_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `customers`");
    }
};
PHP

cat > "$BASE/migrations/2024_01_01_000004_create_products_table.php" <<'PHP'
<?php
require_once __DIR__ . '/_Base.php';

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->table($pdo, 'products', fn() => "
            CREATE TABLE `products` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `sku`        VARCHAR(50) NOT NULL,
                `name`       VARCHAR(150) NOT NULL,
                `category`   ENUM('galon','air','aksesoris','lain') NOT NULL DEFAULT 'galon',
                `price`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `stock`      INT NOT NULL DEFAULT 0,
                `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_products_sku` (`sku`),
                KEY `idx_products_category` (`category`),
                KEY `idx_products_active` (`is_active`),
                KEY `idx_products_stock` (`stock`),
                CONSTRAINT `ck_products_stock` CHECK (`stock` >= 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `products`");
    }
};
PHP

cat > "$BASE/migrations/2024_01_01_000005_create_transactions_table.php" <<'PHP'
<?php
require_once __DIR__ . '/_Base.php';

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->table($pdo, 'transactions', fn() => "
            CREATE TABLE `transactions` (
                `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `invoice_no`    VARCHAR(30) NOT NULL,
                `customer_id`   INT UNSIGNED NOT NULL,
                `user_id`       INT UNSIGNED NOT NULL,
                `type`          ENUM('sale','delivery','return') NOT NULL DEFAULT 'sale',
                `total_amount`  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                `paid_amount`   DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                `status`        ENUM('pending','paid','partial','cancelled') NOT NULL DEFAULT 'pending',
                `notes`         TEXT DEFAULT NULL,
                `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_transactions_invoice` (`invoice_no`),
                KEY `idx_transactions_customer` (`customer_id`),
                KEY `idx_transactions_user` (`user_id`),
                KEY `idx_transactions_status` (`status`),
                KEY `idx_transactions_created` (`created_at`),
                CONSTRAINT `fk_transactions_customer` FOREIGN KEY (`customer_id`)
                    REFERENCES `customers`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`)
                    REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `transactions`");
    }
};
PHP

cat > "$BASE/migrations/2024_01_01_000006_create_transaction_items_table.php" <<'PHP'
<?php
require_once __DIR__ . '/_Base.php';

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->table($pdo, 'transaction_items', fn() => "
            CREATE TABLE `transaction_items` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `transaction_id` INT UNSIGNED NOT NULL,
                `product_id`     INT UNSIGNED NOT NULL,
                `qty`            INT NOT NULL DEFAULT 1,
                `unit_price`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `subtotal`       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                KEY `idx_items_transaction` (`transaction_id`),
                KEY `idx_items_product` (`product_id`),
                CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`)
                    REFERENCES `products`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT `fk_items_transaction` FOREIGN KEY (`transaction_id`)
                    REFERENCES `transactions`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT `ck_items_qty` CHECK (`qty` > 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `transaction_items`");
    }
};
PHP

cat > "$BASE/migrations/2024_01_01_000007_create_schedules_table.php" <<'PHP'
<?php
require_once __DIR__ . '/_Base.php';

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->table($pdo, 'schedules', fn() => "
            CREATE TABLE `schedules` (
                `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `customer_id`   INT UNSIGNED NOT NULL,
                `user_id`       INT UNSIGNED NOT NULL,
                `scheduled_at`  DATETIME NOT NULL,
                `status`        ENUM('pending','on_route','done','cancelled') NOT NULL DEFAULT 'pending',
                `notes`         TEXT DEFAULT NULL,
                `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY `idx_schedules_customer` (`customer_id`),
                KEY `idx_schedules_user` (`user_id`),
                KEY `idx_schedules_status` (`status`),
                KEY `idx_schedules_scheduled_at` (`scheduled_at`),
                CONSTRAINT `fk_schedules_customer` FOREIGN KEY (`customer_id`)
                    REFERENCES `customers`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT `fk_schedules_user` FOREIGN KEY (`user_id`)
                    REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `schedules`");
    }
};
PHP

cat > "$BASE/migrations/2024_01_01_000008_create_logs_table.php" <<'PHP'
<?php
require_once __DIR__ . '/_Base.php';

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->table($pdo, 'logs', fn() => "
            CREATE TABLE `logs` (
                `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id`    INT UNSIGNED DEFAULT NULL,
                `action`     VARCHAR(100) NOT NULL,
                `entity`     VARCHAR(100) DEFAULT NULL,
                `entity_id`  INT UNSIGNED DEFAULT NULL,
                `payload`    JSON DEFAULT NULL,
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_logs_user` (`user_id`),
                KEY `idx_logs_action` (`action`),
                KEY `idx_logs_entity` (`entity`, `entity_id`),
                KEY `idx_logs_created` (`created_at`),
                CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`)
                    REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `logs`");
    }
};
PHP

cat > "$BASE/migrations/2024_01_01_000009_create_jwt_blacklist_table.php" <<'PHP'
<?php
require_once __DIR__ . '/_Base.php';

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->table($pdo, 'jwt_blacklist', fn() => "
            CREATE TABLE `jwt_blacklist` (
                `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `jti`        VARCHAR(64) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_jti` (`jti`),
                KEY `idx_expires` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `jwt_blacklist`");
    }
};
PHP

# ================= SEEDERS =================

cat > "$BASE/seeders/Seeder.php" <<'PHP'
<?php

abstract class Seeder
{
    abstract public function run(PDO $pdo): void;
}
PHP

cat > "$BASE/seeders/RoleSeeder.php" <<'PHP'
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
PHP

cat > "$BASE/seeders/UserSeeder.php" <<'PHP'
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
PHP

cat > "$BASE/seeders/ProductSeeder.php" <<'PHP'
<?php
require_once __DIR__ . '/Seeder.php';

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
            "INSERT INTO products (sku, name, category, price, stock, is_active)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                category = VALUES(category),
                price = VALUES(price)"
        );

        foreach ($products as $p) {
            $stmt->execute([
                $p['sku'], $p['name'], $p['category'],
                $p['price'], $p['stock'], $p['is_active'],
            ]);
        }
    }
};
PHP

cat > "$BASE/seeders/CustomerSeeder.php" <<'PHP'
<?php
require_once __DIR__ . '/Seeder.php';

return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $customers = [
            ['name' => 'Ibu Siti',  'phone' => '081111222333', 'address' => 'Jl. Merdeka No. 1',    'is_active' => 1],
            ['name' => 'Pak Budi',  'phone' => '082222333444', 'address' => 'Jl. Sudirman No. 12',  'is_active' => 1],
            ['name' => 'Toko Jaya', 'phone' => '083333444555', 'address' => 'Jl. Pasar Baru No. 5', 'is_active' => 1],
        ];

        $stmt = $pdo->prepare(
            "INSERT INTO customers (name, phone, address, is_active)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                address = VALUES(address)"
        );

        foreach ($customers as $c) {
            $stmt->execute([$c['name'], $c['phone'], $c['address'], $c['is_active']]);
        }
    }
};
PHP

cat > "$BASE/seeders/DemoSeeder.php" <<'PHP'
<?php
require_once __DIR__ . '/Seeder.php';

return new class extends Seeder {
    public function run(PDO $pdo): void
    {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
        if ($count > 0) {
            echo "(already seeded) ";
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO transactions
                (invoice_no, customer_id, user_id, type, total_amount, paid_amount, status, notes)
            VALUES (?, ?, ?, 'sale', ?, ?, 'paid', 'Transaksi demo')
        ");
        $stmt->execute(['INV-DEMO-0001', 1, 1, 26000.00, 26000.00]);
        $trxId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO transaction_items
                (transaction_id, product_id, qty, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$trxId, 1, 1, 20000.00, 20000.00]);
        $stmt->execute([$trxId, 2, 1, 6000.00,  6000.00]);

        $pdo->exec("UPDATE products SET stock = stock - 1 WHERE id IN (1, 2)");

        $stmt = $pdo->prepare("
            INSERT INTO schedules
                (customer_id, user_id, scheduled_at, status, notes)
            VALUES (?, ?, ?, 'pending', 'Antar galon')
        ");
        $stmt->execute([2, 2, date('Y-m-d H:i:s', time() + 86400)]);
    }
};
PHP

echo "Selesai. File yang dibuat:\n"
ls -1 "$BASE/migrations"
echo "---"
ls -1 "$BASE/seeders"
