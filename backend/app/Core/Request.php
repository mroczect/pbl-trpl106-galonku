<?php
namespace App\Core;

class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;
    private array $headers;
    private array $files;

    public static function capture(): self
    {
        $r = new self();

        $r->method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $r->path    = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
        $r->query   = $_GET;
        $r->headers = self::getHeaders();
        $r->files   = $_FILES;

        $raw  = file_get_contents('php://input');
        $json = json_decode($raw ?: '', true);

        if (is_array($json)) {
            $r->body = $json;
        } elseif (!empty($_POST)) {
            $r->body = $_POST;
        } else {
            parse_str($raw ?: '', $parsed);
            $r->body = $parsed;
        }

        return $r;
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function files(): array { return $this->files; }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->query;
        return $this->query[$key] ?? $default;
    }

    public function body(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->body;
        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization', '');
        if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    public function ip(): ?string
    {
        $trustedProxies = array_filter(
            array_map('trim', explode(',', $_ENV['TRUSTED_PROXIES'] ?? ''))
        );
        $remote = $_SERVER['REMOTE_ADDR'] ?? null;

        if ($remote && in_array($remote, $trustedProxies, true)) {
            $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
            if ($xff) {
                $first = trim(explode(',', $xff)[0]);
                if (filter_var($first, FILTER_VALIDATE_IP)) {
                    return $first;
                }
            }
        }

        return $remote;
    }

    public function userAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    public function validate(array $rules): array
    {
        return Validator::make($this->all(), $rules);
    }

    private static function getHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($name)] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }

        return $headers;
    }

    public static function create(
        string $method,
        string $path,
        array $body = [],
        array $query = [],
        array $headers = []
    ): self {
        $r = new self();
        $r->method  = strtoupper($method);
        $r->path    = rtrim($path, '/') ?: '/';
        $r->query   = $query;
        $r->body    = $body;
        $r->headers = array_change_key_case($headers, CASE_LOWER);
        $r->files   = [];
        return $r;
    }
}
