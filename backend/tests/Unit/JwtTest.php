<?php
namespace Tests\Unit;

use App\Support\Jwt;
use Tests\Support\UnitTestCase;

class JwtTest extends UnitTestCase
{
    public function test_access_token_roundtrip(): void
    {
        $token = Jwt::access(['sub' => 42, 'email' => 'a@b.com']);
        $payload = Jwt::verifyAccess($token);
        $this->assertNotNull($payload);
        $this->assertSame(42, $payload['sub']);
        $this->assertSame('access', $payload['typ']);
        $this->assertArrayHasKey('jti', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function test_refresh_token_roundtrip(): void
    {
        $token = Jwt::refresh(['sub' => 1]);
        $payload = Jwt::verifyRefresh($token);
        $this->assertNotNull($payload);
        $this->assertSame('refresh', $payload['typ']);
    }

    public function test_access_token_rejected_as_refresh(): void
    {
        $token = Jwt::access(['sub' => 1]);
        $this->assertNull(Jwt::verifyRefresh($token));
    }

    public function test_refresh_token_rejected_as_access(): void
    {
        $token = Jwt::refresh(['sub' => 1]);
        $this->assertNull(Jwt::verifyAccess($token));
    }

    public function test_tampered_token_rejected(): void
    {
        $token = Jwt::access(['sub' => 1]);
        $this->assertNull(Jwt::verifyAccess($token . 'x'));
    }

    public function test_jti_is_unique(): void
    {
        $a = Jwt::verifyAccess(Jwt::access(['sub' => 1]));
        $b = Jwt::verifyAccess(Jwt::access(['sub' => 1]));
        $this->assertNotSame($a['jti'], $b['jti']);
    }
}
