<?php
declare(strict_types=1);

namespace Database\Migration;

use PDO;

final class Repository
{
    public function __construct(private PDO $pdo) {}

    public function ensureTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration`   VARCHAR(255) NOT NULL,
                `batch`       INT UNSIGNED NOT NULL,
                `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_migrations_name`  (`migration`),
                KEY        `idx_migrations_batch` (`batch`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function ran(): array
    {
        $stmt = $this->pdo->query('SELECT migration FROM migrations ORDER BY id ASC');
        $rows = [];
        while (($row = $stmt->fetchColumn()) !== false) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function nextBatch(): int
    {
        $max = $this->pdo
            ->query('SELECT COALESCE(MAX(batch), 0) FROM migrations')
            ->fetchColumn();
        return 1 + (int) $max;
    }

    public function batchesDescending(int $limit): array
    {
        $limit = max(1, $limit);
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT batch FROM migrations ORDER BY batch DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function countBatches(): int
    {
        return (int) $this->pdo
            ->query('SELECT COUNT(DISTINCT batch) FROM migrations')
            ->fetchColumn();
    }

    public function namesInBatch(int $batch): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC'
        );
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function record(string $name, int $batch): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO migrations (migration, batch) VALUES (?, ?)'
        );
        $stmt->execute([$name, $batch]);
    }

    public function forget(string $name): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM migrations WHERE migration = ?');
        $stmt->execute([$name]);
    }
}
