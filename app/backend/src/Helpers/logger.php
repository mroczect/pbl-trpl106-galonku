<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

if (!function_exists('app_logger')) {
    function app_logger(): Logger {
        static $logger = null;
        if ($logger === null) {
            $logger = new Logger('galonku');
            $logger->pushHandler(new StreamHandler(
                __DIR__ . '/../../storage/logs/app.log',
                Logger::DEBUG
            ));
        }
        return $logger;
    }
}

if (!function_exists('log_action')) {
    function log_action(
        ?int $userId,
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        ?array $payload = null
    ): void {
        try {
            \App\Models\Log::create([
                'user_id'    => $userId,
                'action'     => $action,
                'entity'     => $entity,
                'entity_id'  => $entityId,
                'payload'    => $payload,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            app_logger()->info("[$action] " . ($entity ?? '-') . " #" . ($entityId ?? '-'), [
                'user_id' => $userId,
                'payload' => $payload,
            ]);
        } catch (\Throwable $e) {
            app_logger()->error("Gagal log: " . $e->getMessage());
        }
    }
}
