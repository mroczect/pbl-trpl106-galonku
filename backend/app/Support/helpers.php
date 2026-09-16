<?php

use App\Support\AppLogger;

if (!function_exists('log_action')) {
    function log_action(
        ?int $userId,
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        ?array $payload = null
    ): void {
        AppLogger::action($userId, $action, $entity, $entityId, $payload);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        [$file, $path] = array_pad(explode('.', $key, 2), 2, null);
        $filePath = dirname(__DIR__, 2) . "/config/$file.php";
        if (!file_exists($filePath)) return $default;
        $config = require $filePath;
        if ($path === null) return $config;
        return $config[$path] ?? $default;
    }
}
