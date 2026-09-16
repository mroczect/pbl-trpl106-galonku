<?php
namespace App\Models;

use App\Core\{Model, Database};

class Schedule extends Model
{
    protected static string $table = 'schedules';
    protected static array $fillable = [
        'customer_id', 'user_id', 'scheduled_at', 'status', 'notes',
    ];

    public static function paginateWithRelations(int $page, int $perPage, array $filters = []): array
    {
        $page    = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $offset  = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        foreach (['status', 'user_id', 'customer_id'] as $col) {
            if (!empty($filters[$col])) {
                $where[] = "s.$col = ?";
                $params[] = $filters[$col];
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $db = Database::connect();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM schedules s $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT s.*, c.name AS customer_name, u.name AS user_name
                FROM schedules s
                JOIN customers c ON c.id = s.customer_id
                JOIN users u ON u.id = s.user_id
                $whereSql
                ORDER BY s.scheduled_at DESC
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
