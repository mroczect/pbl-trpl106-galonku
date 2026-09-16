<?php
namespace Tests\Support;

use PHPUnit\Framework\Assert;

class TestResponse
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = []
    ) {}

    public function json(?string $path = null, mixed $default = null): mixed
    {
        $data = json_decode($this->body, true) ?? [];
        if ($path === null) return $data;

        $current = $data;
        foreach (explode('.', $path) as $seg) {
            if (!is_array($current) || !array_key_exists($seg, $current)) {
                return $default;
            }
            $current = $current[$seg];
        }
        return $current;
    }

    public function assertStatus(int $code): self
    {
        Assert::assertSame($code, $this->status, "Expected $code, got {$this->status}. Body: {$this->body}");
        return $this;
    }

    public function assertOk(): self
    {
        Assert::assertTrue(
            $this->status >= 200 && $this->status < 300,
            "Expected 2xx, got {$this->status}. Body: {$this->body}"
        );
        return $this;
    }

    public function assertUnauthorized(): self { return $this->assertStatus(401); }
    public function assertForbidden(): self    { return $this->assertStatus(403); }
    public function assertNotFound(): self     { return $this->assertStatus(404); }
    public function assertUnprocessable(): self{ return $this->assertStatus(422); }
    public function assertTooManyRequests(): self { return $this->assertStatus(429); }

    public function assertJsonPath(string $path, mixed $expected): self
    {
        Assert::assertSame($expected, $this->json($path), "Path [$path] mismatch. Body: {$this->body}");
        return $this;
    }

    public function assertJsonHas(string $key): self
    {
        Assert::assertNotNull($this->json($key), "Missing key: $key. Body: {$this->body}");
        return $this;
    }

    public function assertJsonMissing(string $key): self
    {
        Assert::assertNull($this->json($key), "Key should not exist: $key");
        return $this;
    }

    public function assertSuccess(): self
    {
        return $this->assertJsonPath('success', true);
    }

    public function assertFailed(): self
    {
        return $this->assertJsonPath('success', false);
    }

    public function dump(): self
    {
        fwrite(STDERR, "\n\033[33m[{$this->status}]\033[0m {$this->body}\n");
        return $this;
    }
}
