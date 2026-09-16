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
