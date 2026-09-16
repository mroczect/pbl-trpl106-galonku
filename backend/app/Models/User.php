<?php
namespace App\Models;

use App\Core\{Model, Database};

class User extends Model
{
    protected static string $table = 'users';
    protected static array $fillable = [
        'role_id', 'name', 'email', 'phone',
        'password_hash', 'is_active', 'last_login_at',
    ];

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function paginateWithRole(int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $offset  = ($page - 1) * $perPage;

        $db = Database::connect();

        $total = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        $stmt = $db->prepare(
            "SELECT u.id, u.role_id, u.name, u.email, u.phone, u.is_active,
                    u.last_login_at, u.created_at, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             ORDER BY u.id DESC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute();

        return [
            'data'        => $stmt->fetchAll(),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    public static function updateLastLogin(int $id): void
    {
        $stmt = Database::connect()->prepare(
            "UPDATE users SET last_login_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    public static function emailExists(string $email): bool
    {
        $stmt = Database::connect()->prepare(
            "SELECT 1 FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        return (bool) $stmt->fetchColumn();
    }

    public static function updatePassword(int $id, string $hash): bool
    {
        $stmt = Database::connect()->prepare(
            "UPDATE users SET password_hash = ? WHERE id = ?"
        );
        return $stmt->execute([$hash, $id]);
    }
}
