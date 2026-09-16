<?php

namespace App\Core;

use App\Models\User;
use App\Support\Jwt;

class Auth
{
    private static ?array $user = null;
    private static ?Request $request = null;

    public static function setRequest(Request $req): void
    {
        self::$request = $req;
        self::$user    = null; 
    }

    public static function reset(): void
    {
        self::$request = null;
        self::$user    = null;
    }

    private static function req(): Request
    {
        return self::$request ?? Request::capture();
    }

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
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($plain, PASSWORD_ARGON2ID);
        }
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    public static function login(array $user): array
    {
        $accessToken  = Jwt::access([
            'sub'   => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role_name'],
        ]);

        $refreshToken = Jwt::refresh([
            'sub' => $user['id'],
        ]);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => (int) ($_ENV['JWT_ACCESS_EXPIRE'] ?? 3600),
        ];
    }

    public static function currentUser(): ?array
    {
        if (self::$user !== null) return self::$user;

        $token = self::req()->bearerToken();
        if (!$token) return null;

        $payload = Jwt::verifyAccess($token);
        if (!$payload || !isset($payload['sub'])) return null;

        if (Jwt::isBlacklisted($token)) return null;

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

    public static function logout(string $token): void
    {
        Jwt::blacklist($token);
    }
}
