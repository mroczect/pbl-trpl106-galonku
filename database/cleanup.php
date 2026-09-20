<?php
require __DIR__ . '/../backend/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;

Dotenv::createImmutable(dirname(__DIR__) . '/backend')->safeLoad();

$pdo = Database::connect();

$deleted = $pdo->exec("DELETE FROM jwt_blacklist WHERE expires_at < NOW()");
echo "Cleaned jwt_blacklist: $deleted rows\n";

$cacheDir = __DIR__ . '/../backend/storage/cache';
$cutoff   = time() - 3600;

foreach (glob($cacheDir . '/rl_*.json') as $f) {
    if (filemtime($f) < $cutoff) @unlink($f);
}

echo "Cleaned rate limit cache\n";
