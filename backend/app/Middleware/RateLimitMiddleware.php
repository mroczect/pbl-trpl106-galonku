<?php
namespace App\Middleware;

use App\Core\{Request, Response};

class RateLimitMiddleware
{
    public static function handle(Request $req, ?string $key = null, ?int $max = null, ?int $window = null): void
    {
        $max    = $max    ?? (int) ($_ENV['RATE_LIMIT_LOGIN']  ?? 5);
        $window = $window ?? (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 60);
        $key    = $key ?? ($req->ip() . ':' . $req->path());

        $dir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $file = $dir . '/rl_' . md5($key) . '.json';

        $data = ['count' => 0, 'reset' => time() + $window];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?: $data;
        }

        if ($data['reset'] < time()) {
            $data = ['count' => 0, 'reset' => time() + $window];
        }

        $data['count']++;

        file_put_contents($file, json_encode($data));

        if ($data['count'] > $max) {
            Response::error('Terlalu banyak permintaan. Coba lagi nanti.', 429, null, [
                'Retry-After' => (string) max(1, $data['reset'] - time()),
            ]);
        }
    }
}
