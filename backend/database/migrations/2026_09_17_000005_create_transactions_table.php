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
