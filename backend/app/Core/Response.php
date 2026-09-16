<?php
namespace App\Core;

use App\Testing\ResponseCaptured;

class Response
{
    public static bool $testMode = false;

    public static function json(mixed $data, int $status = 200, array $headers = []): void
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (self::$testMode) {
            throw new ResponseCaptured($status, $body ?: '', $headers);
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        echo $body;
        exit;
    }

    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        array $meta = []
    ): void {
        $payload = ['success' => true, 'message' => $message, 'data' => $data];
        if ($meta) $payload['meta'] = $meta;
        self::json($payload, $status);
    }

    public static function error(
        string $message,
        int $status = 400,
        mixed $errors = null,
        array $headers = []
    ): void {
        self::json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status, $headers);
    }

    public static function noContent(): void
    {
        if (self::$testMode) {
            throw new ResponseCaptured(204, '', []);
        }
        http_response_code(204);
        exit;
    }
}
