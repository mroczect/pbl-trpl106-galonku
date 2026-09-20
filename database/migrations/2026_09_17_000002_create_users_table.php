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
