<?php
namespace App\Core;

use App\Models\User;

class Auth
{
    private static ?array $user = null;

    public static function attempt(string $email, string $password): ?array
    {
        $user = User::findByEmail($email);
        if (!$user) return null;
        if (!password_verify($password, $user['password_hash'])) return null;
        if ((int) $user['is_active'] !== 1) return null;
        return $user;
    }

    public static function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    public static function currentUser(): ?array
    {
        if (self::$user !== null) return self::$user;

        $token = Request::capture()->bearerToken();
        if (!$token) return null;

        $payload = verify_jwt($token);
        if (!$payload || !isset($payload['sub'])) return null;

        self::$user = User::find((int) $payload['sub']);
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::currentUser()['id'] ?? null;
    }

    public static function roleName(): ?string
    {
        return self::currentUser()['role_name'] ?? null;
    }
}
