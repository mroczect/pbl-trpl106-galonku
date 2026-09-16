<?php
namespace Tests\Feature;

use Tests\Support\TestCase;

class RateLimitTest extends TestCase
{
    public function test_login_blocked_after_5_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $res = $this->post('/api/v1/auth/login', [
                'email' => 'admin@galonku.com', 'password' => 'wrong',
            ]);
            $this->assertNotSame(429, $res->status, "Attempt #" . ($i + 1) . " should not be rate-limited");
        }

        $blocked = $this->post('/api/v1/auth/login', [
            'email' => 'admin@galonku.com', 'password' => 'wrong',
        ]);
        $blocked->assertTooManyRequests();
    }

    public function test_different_endpoints_have_separate_buckets(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/api/v1/auth/login', [
                'email' => 'x@y.com', 'password' => 'wrong',
            ]);
        }
        $res = $this->post('/api/v1/auth/register', [
            'name' => 'Fresh', 'email' => 'fresh@test.com', 'password' => 'secret123',
        ]);
        $this->assertNotSame(429, $res->status);
    }
}
