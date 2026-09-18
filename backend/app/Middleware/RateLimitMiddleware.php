<?php
namespace App\Middleware;

use App\Core\{Request, Response};

class RateLimitMiddleware
{
    public static function handle(
        Request $req,
        ?string $key = null,
        string|int|null $max = null,
        string|int|null $window = null
    ): void {
        $max    = $max    !== null ? (int) $max    : (int) ($_ENV['RATE_LIMIT_LOGIN']  ?? 5);
        $window = $window !== null ? (int) $window : (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 60);
        $key    = $key ?? ($req->ip() . ':' . $req->path());

        $dir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $file = $dir . '/rl_' . md5($key) . '.json';
        $now  = time();

        $fp = fopen($file, 'c+');
        if (!$fp) {
            return;
        }

        try {
            if (!flock($fp, LOCK_EX)) {
                return;
            }

            $size = filesize($file);
            $raw  = $size > 0 ? fread($fp, $size) : '';
            $data = json_decode($raw ?: '', true);

            if (!is_array($data) || ($data['reset'] ?? 0) < $now) {
                $data = ['count' => 0, 'reset' => $now + $window];
            }

            $data['count']++;

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($data));
            fflush($fp);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        if ($data['count'] > $max) {
            Response::error('Too many requests. Please try again later.', 429, null, [
                'Retry-After' => (string) max(1, $data['reset'] - $now),
            ]);
        }
    }
}
