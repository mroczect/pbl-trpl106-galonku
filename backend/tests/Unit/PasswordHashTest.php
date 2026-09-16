<?php
namespace Tests\Unit;

use App\Core\Auth;
use Tests\Support\UnitTestCase;

class PasswordHashTest extends UnitTestCase
{
    public function test_hash_produces_verifiable_hash(): void
    {
        $hash = Auth::hash('rahasia123');
        $this->assertNotSame('rahasia123', $hash);
        $this->assertTrue(password_verify('rahasia123', $hash));
    }

    public function test_same_password_produces_different_hash(): void
    {
        $this->assertNotSame(Auth::hash('abc'), Auth::hash('abc'));
    }

    public function test_wrong_password_fails_verify(): void
    {
        $hash = Auth::hash('benar');
        $this->assertFalse(password_verify('salah', $hash));
    }
}
