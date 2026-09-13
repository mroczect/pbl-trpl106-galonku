<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('generate_jwt')) {
    function generate_jwt(array $payload): string {
        $payload['iat'] = time();
        $payload['exp'] = time() + (int) ($_ENV['JWT_EXPIRE'] ?? 3600);
        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }
}

if (!function_exists('verify_jwt')) {
    function verify_jwt(string $token): ?array {
        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            return (array) $decoded;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
