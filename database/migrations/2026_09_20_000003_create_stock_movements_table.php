<?php
declare(strict_types=1);

use Database\Migration\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->createTable($pdo, 'stock_movements', fn() => "
            CREATE TABLE `stock_movements` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `product_id`     INT UNSIGNED NOT NULL,
                `user_id`        INT UNSIGNED DEFAULT NULL,
                `type`           ENUM('in','out','adjustment') NOT NULL,
                `qty`            INT NOT NULL,
                `stock_before`   INT NOT NULL,
                `stock_after`    INT NOT NULL,
                `reason`         VARCHAR(100) NOT NULL,
                `reference_type` VARCHAR(50) DEFAULT NULL,
                `reference_id`   INT UNSIGNED DEFAULT NULL,
                `notes`          TEXT DEFAULT NULL,
                `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_sm_product`  (`product_id`),
                KEY `idx_sm_type`     (`type`),
                KEY `idx_sm_user`     (`user_id`),
                KEY `idx_sm_created`  (`created_at`),
                CONSTRAINT `fk_sm_product` FOREIGN KEY (`product_id`)
                    REFERENCES `products`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT `fk_sm_user` FOREIGN KEY (`user_id`)
                    REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'stock_movements');
    }
};
