<?php
namespace App\Models;

use App\Core\Database;

class User
{
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

    public static function create(array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $ph   = implode(', ', array_fill(0, count($data), '?'));
        $stmt = Database::connect()->prepare(
            "INSERT INTO users ($cols) VALUES ($ph)"
        );
        $stmt->execute(array_values($data));
        return (int) Database::connect()->lastInsertId();
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
}
