<?php
namespace App\Models;

use App\Core\Database;

class StockMovement
{
    public static function create(array $data): int
    {
        $stmt = Database::connect()->prepare(
            "INSERT INTO stock_movements
             (product_id, user_id, type, qty, stock_before, stock_after,
              reason, reference_type, reference_id, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['product_id'],
            $data['user_id']         ?? null,
            $data['type'],
            $data['qty'],
            $data['stock_before'],
            $data['stock_after'],
            $data['reason'],
            $data['reference_type']  ?? null,
            $data['reference_id']    ?? null,
            $data['notes']           ?? null,
        ]);
        return (int) Database::connect()->lastInsertId();
    }

    public static function paginate(int $page, int $perPage, array $filters = []): array
    {
        $page    = max(1, $page);
        $perPage = min(200, max(1, $perPage));
        $offset  = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        if (!empty($filters['product_id'])) {
            $where[]  = "sm.product_id = ?";
            $params[] = $filters['product_id'];
        }
        if (!empty($filters['type'])) {
            $where[]  = "sm.type = ?";
            $params[] = $filters['type'];
        }
        if (!empty($filters['user_id'])) {
            $where[]  = "sm.user_id = ?";
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['from'])) {
            $where[]  = "sm.created_at >= ?";
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[]  = "sm.created_at <= ?";
            $params[] = $filters['to'] . ' 23:59:59';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $db = Database::connect();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM stock_movements sm $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT sm.*, p.name AS product_name, p.sku,
                       u.name AS user_name
                FROM stock_movements sm
                JOIN products p ON p.id = sm.product_id
                LEFT JOIN users u ON u.id = sm.user_id
                $whereSql
                ORDER BY sm.id DESC
                LIMIT $perPage OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return [
            'data'        => $stmt->fetchAll(),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }
}
