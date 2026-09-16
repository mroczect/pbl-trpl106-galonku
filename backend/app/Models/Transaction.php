<?php
namespace App\Models;

use App\Core\{Model, Database};

class Transaction extends Model
{
    protected static string $table = 'transactions';
    protected static array $fillable = [
        'invoice_no', 'customer_id', 'user_id', 'type',
        'total_amount', 'paid_amount', 'status', 'notes',
    ];

    public static function paginateWithRelations(int $page, int $perPage, array $filters = []): array
    {
        $page    = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $offset  = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        foreach (['status', 'customer_id', 'user_id', 'type'] as $col) {
            if (!empty($filters[$col])) {
                $where[] = "t.$col = ?";
                $params[] = $filters[$col];
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $db = Database::connect();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM transactions t $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT t.*, c.name AS customer_name, u.name AS user_name
                FROM transactions t
                JOIN customers c ON c.id = t.customer_id
                JOIN users u ON u.id = t.user_id
                $whereSql
                ORDER BY t.id DESC
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

    public static function findWithItems(int $id): ?array
    {
        $db = Database::connect();

        $stmt = $db->prepare(
            "SELECT t.*, c.name AS customer_name, c.phone AS customer_phone,
                    u.name AS user_name
             FROM transactions t
             JOIN customers c ON c.id = t.customer_id
             JOIN users u ON u.id = t.user_id
             WHERE t.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $trx = $stmt->fetch();
        if (!$trx) return null;

        $itemsStmt = $db->prepare(
            "SELECT ti.*, p.name AS product_name, p.sku
             FROM transaction_items ti
             JOIN products p ON p.id = ti.product_id
             WHERE ti.transaction_id = ?"
        );
        $itemsStmt->execute([$id]);
        $trx['items'] = $itemsStmt->fetchAll();

        return $trx;
    }
}
