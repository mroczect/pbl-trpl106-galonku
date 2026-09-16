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
