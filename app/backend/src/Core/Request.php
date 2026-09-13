<?php
namespace App\Core;

class Request
{
    public string $method;
    public string $path;
    public array  $query;
    public array  $body;
    public array  $headers;

    public static function capture(): self
    {
        $r = new self();
        $r->method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $r->path    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $r->query   = $_GET;
        $r->headers = self::getHeaders();

        $raw  = file_get_contents('php://input');
        $json = json_decode($raw, true);
        $r->body = is_array($json) ? $json : $_POST;

        return $r;
    }

    private static function getHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->headers['AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            return trim($m[1]);
        }
        return null;
    }
}
