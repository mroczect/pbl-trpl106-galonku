<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            $config = require dirname(__DIR__, 2) . '/config/database.php';

            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $config['host'], $config['port'],
                $config['database'], $config['charset']
            );

            try {
                self::$pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                throw new \RuntimeException('DB connection failed: ' . $e->getMessage());
            }
        }

        return self::$pdo;
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connect();

        if ($pdo->inTransaction()) {
            $sp = 'sp_' . bin2hex(random_bytes(8));
            $pdo->exec("SAVEPOINT `$sp`");

            try {
                $result = $callback($pdo);
                $pdo->exec("RELEASE SAVEPOINT `$sp`");
                return $result;
            } catch (\Throwable $e) {
                try {
                    $pdo->exec("ROLLBACK TO SAVEPOINT `$sp`");
                    $pdo->exec("RELEASE SAVEPOINT `$sp`");
                } catch (\Throwable) {
                }
                throw $e;
            }
        }

        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }
}
