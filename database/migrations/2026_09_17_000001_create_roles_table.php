<?php
declare(strict_types=1);

use Database\Migration\Migration;

return new class extends Migration {
    public function up(PDO $pdo): void
    {
        $this->createTable($pdo, 'roles', fn() => "
            CREATE TABLE `roles` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`        VARCHAR(50) NOT NULL,
                `description` VARCHAR(255) DEFAULT NULL,
                `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_roles_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
        $this->dropTable($pdo, 'roles');
    }
};
