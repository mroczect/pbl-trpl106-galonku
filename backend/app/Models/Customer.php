<?php
namespace App\Models;

use App\Core\{Model, Database};

class Customer extends Model
{
    protected static string $table = 'customers';
    protected static array $fillable = [
        'name', 'phone', 'address', 'notes', 'is_active',
    ];

    public static function phoneExists(string $phone, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM customers WHERE phone = ?";
        $params = [$phone];
        if ($exceptId !== null) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }
        $sql .= " LIMIT 1";

        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }
}
