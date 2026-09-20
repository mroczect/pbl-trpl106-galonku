<?php
declare(strict_types=1);

if (defined('DATABASE_BOOTSTRAPPED')) {
    return;
}
define('DATABASE_BOOTSTRAPPED', true);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Database\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file     = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

 $vendor = __DIR__ . '/../backend/vendor/autoload.php';
if (!is_file($vendor)) {
    throw new \RuntimeException('Missing backend/vendor/autoload.php — run composer install');
}
require_once $vendor;

 $dbName = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE');
if (!is_string($dbName) || $dbName === '') {
    $envName = (getenv('APP_ENV') ?: 'local') === 'testing' ? '.env.testing' : '.env';
    $envDir  = dirname(__DIR__) . '/backend';
    if (is_file($envDir . '/' . $envName)) {
        \Dotenv\Dotenv::createImmutable($envDir, $envName)->safeLoad();
    }
}
