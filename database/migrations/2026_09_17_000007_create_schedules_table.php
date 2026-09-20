<?php
declare(strict_types=1);

use Database\Migration\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->createTable($pdo, 'schedules', fn() => "
            CREATE TABLE `schedules` (
                `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `customer_id`   INT UNSIGNED NOT NULL,
                `user_id`       INT UNSIGNED NOT NULL,
                `scheduled_at`  DATETIME NOT NULL,
                `status`        ENUM('pending','on_route','done','cancelled') NOT NULL DEFAULT 'pending',
                `notes`         TEXT DEFAULT NULL,
                `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY `idx_schedules_customer`      (`customer_id`),
                KEY `idx_schedules_user`          (`user_id`),
                KEY `idx_schedules_status`        (`status`),
                KEY `idx_schedules_scheduled_at`  (`scheduled_at`),
                CONSTRAINT `fk_schedules_customer` FOREIGN KEY (`customer_id`)
                    REFERENCES `customers`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT `fk_schedules_user` FOREIGN KEY (`user_id`)
                    REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'schedules');
    }
};
