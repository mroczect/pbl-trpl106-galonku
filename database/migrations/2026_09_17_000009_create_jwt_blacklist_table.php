<?php
declare(strict_types=1);

use Database\Migration\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->createTable($pdo, 'jwt_blacklist', fn() => "
            CREATE TABLE `jwt_blacklist` (
                `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `jti`        VARCHAR(64) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_jti`         (`jti`),
                KEY `idx_expires`           (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'jwt_blacklist');
    }
};
