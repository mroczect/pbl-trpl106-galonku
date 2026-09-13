<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$db = Database::connect();

$files = ['galonku_full.sql', 'seed.sql'];
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) {
        echo "⚠  Skip (tidak ada): $file\n";
        continue;
    }
    echo "▶ Menjalankan: $file\n";
    $sql = file_get_contents($path);
    $db->exec($sql);
}

echo "✅ Migrasi selesai.\n";
