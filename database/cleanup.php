<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Database\Connection;
use Database\Support\Output;

 $pdo = Connection::database();

 $deleted = $pdo->exec('DELETE FROM jwt_blacklist WHERE expires_at < NOW()');
Output::ok('jwt_blacklist: removed ' . (int) $deleted . ' expired entr(ies)');

 $cacheDir = dirname(__DIR__) . '/backend/storage/cache';
 $cutoff   = time() - 3600;
 $removed  = 0;

if (is_dir($cacheDir)) {
    foreach (new DirectoryIterator($cacheDir) as $file) {
        if ($file->isDot() || !$file->isFile()) {
            continue;
        }
        $filename = $file->getFilename();
        if (!str_starts_with($filename, 'rl_') || !str_ends_with($filename, '.json')) {
            continue;
        }
        if ($file->getMTime() >= $cutoff) {
            continue;
        }
        $path = $file->getPathname();
        if (unlink($path)) {
            $removed++;
        } else {
            Output::warn('Failed to remove stale cache file: ' . $path);
        }
    }
}
Output::ok("rate-limit cache: removed {$removed} stale file(s)");
