<?php
namespace App\Models;

use App\Core\Database;

class Log
{
    public static function create(array $data): int
    {
        // Encode payload ke JSON kalau ada
        if (isset($data['payload']) && is_array($data['payload'])) {
            $data['payload'] = json_encode($data['payload'], JSON_UNESCAPED_UNICODE);
        }

        $cols = implode(', ', array_keys($data));
        $ph   = implode(', ', array_fill(0, count($data), '?'));
        $stmt = Database::connect()->prepare(
            "INSERT INTO logs ($cols) VALUES ($ph)"
        );
        $stmt->execute(array_values($data));
        return (int) Database::connect()->lastInsertId();
    }

    public static function latest(int $limit = 100): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT l.*, u.name AS user_name
             FROM logs l
             LEFT JOIN users u ON u.id = l.user_id
             ORDER BY l.id DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
