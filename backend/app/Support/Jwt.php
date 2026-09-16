<?php
namespace App\Support;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use App\Core\Database;

class Jwt
{
    public static function access(array $payload): string
    {
        return self::encode($payload, 'access');
    }

    public static function refresh(array $payload): string
    {
        return self::encode($payload, 'refresh');
    }

    private static function encode(array $payload, string $type): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? 'secret';
        $ttl = $type === 'access'
            ? (int) ($_ENV['JWT_ACCESS_EXPIRE'] ?? 3600)
            : (int) ($_ENV['JWT_REFRESH_EXPIRE'] ?? 2592000);

        $payload['iat'] = time();
        $payload['exp'] = time() + $ttl;
        $payload['typ'] = $type;
        $payload['jti'] = bin2hex(random_bytes(16));

        return FirebaseJWT::encode($payload, $secret, 'HS256');
    }

    public static function verifyAccess(string $token): ?array
    {
        $decoded = self::decode($token);
        if (!$decoded) return null;
        if (($decoded['typ'] ?? '') !== 'access') return null;
        return $decoded;
    }

    public static function verifyRefresh(string $token): ?array
    {
        $decoded = self::decode($token);
        if (!$decoded) return null;
        if (($decoded['typ'] ?? '') !== 'refresh') return null;
        return $decoded;
    }

    private static function decode(string $token): ?array
    {
        try {
            $secret  = $_ENV['JWT_SECRET'] ?? 'secret';
            $decoded = FirebaseJWT::decode($token, new Key($secret, 'HS256'));
            return (array) $decoded;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function blacklist(string $token): void
    {
        $decoded = self::decode($token);
        if (!$decoded || !isset($decoded['jti'])) return;

        try {
            $stmt = Database::connect()->prepare(
                "INSERT INTO jwt_blacklist (jti, expires_at, created_at)
                 VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE created_at = NOW()"
            );
            $stmt->execute([
                $decoded['jti'],
                date('Y-m-d H:i:s', (int) $decoded['exp']),
            ]);
        } catch (\Throwable $e) {
        }
    }

    public static function isBlacklisted(string $token): bool
    {
        $decoded = self::decode($token);
        if (!$decoded || !isset($decoded['jti'])) return false;

        try {
            $stmt = Database::connect()->prepare(
                "SELECT 1 FROM jwt_blacklist WHERE jti = ? LIMIT 1"
            );
            $stmt->execute([$decoded['jti']]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
