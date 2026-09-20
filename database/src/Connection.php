<?php
declare(strict_types=1);

namespace Database;

use Database\Support\Guard;
use PDO;

final class Connection
{
    private static ?array $config = null;

    public static function config(): array
    {
        if (self::$config === null) {
            $path = dirname(__DIR__, 2) . '/backend/config/database.php';
            Guard::assertFileExists($path, 'Database config');

            $config = require $path;
            if (!is_array($config)) {
                throw new \RuntimeException("Database config must return an array: {$path}");
            }
            self::$config = $config;
        }
        return self::$config;
    }

    public static function server(): PDO
    {
        $c   = self::config();
        $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', $c['host'], $c['port'], $c['charset']);
        return new PDO($dsn, $c['username'], $c['password'], self::options());
    }

    public static function database(): PDO
    {
        $c  = self::config();
        $db = $_ENV['DB_DATABASE'] ?? $c['database'] ?? 'galonku_db';
        Guard::assertSafeDatabaseName($db);

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $c['host'], $c['port'], $db, $c['charset']
        );
        return new PDO($dsn, $c['username'], $c['password'], self::options());
    }

    private static function options(): array
    {
        return [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];
    }
}