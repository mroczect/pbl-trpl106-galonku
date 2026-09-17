<?php
namespace App\Support;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use App\Models\Log;

class AppLogger
{
    private static ?Logger $logger = null;

    public static function logger(): Logger
    {
        if (self::$logger === null) {
            $path = dirname(__DIR__, 2) . '/storage/logs/app.log';
            self::$logger = new Logger('galonku');
            self::$logger->pushHandler(new StreamHandler($path, Level::Debug));
        }
        return self::$logger;
    }

    public static function action(
        ?int $userId,
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        ?array $payload = null
    ): void {
        try {
            Log::create([
                'user_id'    => $userId,
                'action'     => $action,
                'entity'     => $entity,
                'entity_id'  => $entityId,
                'payload'    => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            self::logger()->info("[$action] " . ($entity ?? '-') . " #" . ($entityId ?? '-'), [
                'user_id' => $userId,
                'payload' => $payload,
            ]);
        } catch (\Throwable $e) {
            self::logger()->error('Failed to log: ' . $e->getMessage());
        }
    }
}
