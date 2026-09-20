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

            try {
                $dir = dirname($path);
                if (!is_dir($dir)) mkdir($dir, 0775, true);

                self::$logger = new Logger('galonku');
                self::$logger->pushHandler(new StreamHandler($path, Level::Debug));
            } catch (\Throwable $e) {
                self::$logger = new Logger('galonku');
                self::$logger->pushHandler(new \Monolog\Handler\NullHandler());
            }
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
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $trustedProxies = array_filter(
                array_map('trim', explode(',', $_ENV['TRUSTED_PROXIES'] ?? ''))
            );
            if ($ip && in_array($ip, $trustedProxies, true)) {
                $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
                if ($xff) {
                    $first = trim(explode(',', $xff)[0]);
                    if (filter_var($first, FILTER_VALIDATE_IP)) {
                        $ip = $first;
                    }
                }
            }

            Log::create([
                'user_id'    => $userId,
                'action'     => $action,
                'entity'     => $entity,
                'entity_id'  => $entityId,
                'payload'    => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $ip,
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
