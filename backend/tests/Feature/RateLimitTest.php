<?php
namespace Tests\Feature;

use Tests\Support\TestCase;

class RateLimitTest extends TestCase
{
    public function test_login_blocked_after_5_attempts(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.10.0.1';

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
        $_SERVER['REMOTE_ADDR'] = '10.10.0.2';

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

    public function test_rate_limit_is_per_ip(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.10.0.1';
        for ($i = 0; $i < 5; $i++) {
            $this->post('/api/v1/auth/login', [
                'email' => 'x@y.com', 'password' => 'wrong',
            ]);
        }
        $blocked = $this->post('/api/v1/auth/login', [
            'email' => 'x@y.com', 'password' => 'wrong',
        ]);
        $blocked->assertTooManyRequests();

        $_SERVER['REMOTE_ADDR'] = '10.10.0.2';
        $fresh = $this->post('/api/v1/auth/login', [
            'email' => 'x@y.com', 'password' => 'wrong',
        ]);
        $this->assertNotSame(
            429,
            $fresh->status,
            'IP 10.10.0.2 should NOT be rate-limited by IP 10.10.0.1'
        );

        $_SERVER['REMOTE_ADDR'] = '10.10.0.1';
        $stillBlocked = $this->post('/api/v1/auth/login', [
            'email' => 'x@y.com', 'password' => 'wrong',
        ]);
        $stillBlocked->assertTooManyRequests();
    }
}
