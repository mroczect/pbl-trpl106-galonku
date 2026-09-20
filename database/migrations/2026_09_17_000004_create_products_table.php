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
