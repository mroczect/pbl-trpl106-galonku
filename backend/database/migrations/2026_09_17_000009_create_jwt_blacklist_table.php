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
