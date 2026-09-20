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
