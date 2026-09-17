<?php
namespace Tests\Feature;

use App\Models\User;
use Tests\Support\TestCase;

class AuthTest extends TestCase
{
    // ==================== REGISTER ====================

    public function test_register_creates_user(): void
    {
        $res = $this->post('/api/v1/auth/register', [
            'name'     => 'Budi Santoso',
            'email'    => 'budi@example.com',
            'password' => 'secret123',
            'phone'    => '081234567899',
        ]);

        $res->assertStatus(201)->assertSuccess();
        $this->assertNotNull($res->json('data.id'));

        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
        $user = User::findByEmail('budi@example.com');
        $this->assertSame('Budi Santoso', $user['name']);
        $this->assertTrue(password_verify('secret123', $user['password_hash']));
        $this->assertSame('pelanggan', $user['role_name']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        $this->post('/api/v1/auth/register', [
            'name' => 'Dup', 'email' => 'admin@galonku.com', 'password' => 'secret123',
        ])->assertStatus(409)->assertFailed();
    }

    public function test_register_validates_input(): void
    {
        $res = $this->post('/api/v1/auth/register', [
            'name' => 'X', 'email' => 'not-email', 'password' => '12',
        ]);
        $res->assertUnprocessable();
        $this->assertIsArray($res->json('errors'));
        $this->assertArrayHasKey('name', $res->json('errors'));
        $this->assertArrayHasKey('email', $res->json('errors'));
        $this->assertArrayHasKey('password', $res->json('errors'));
    }


    public function test_login_success_returns_tokens(): void
    {
        $res = $this->post('/api/v1/auth/login', [
            'email' => 'admin@galonku.com', 'password' => 'admin123',
        ]);

        $res->assertOk()->assertSuccess();
        $res->assertJsonPath('data.user.email', 'admin@galonku.com');
        $res->assertJsonPath('data.user.role_name', 'admin');
        $this->assertNotEmpty($res->json('data.access_token'));
        $this->assertNotEmpty($res->json('data.refresh_token'));
        $this->assertSame('Bearer', $res->json('data.token_type'));
    }

    public function test_login_wrong_password_returns_401(): void
    {
        $this->post('/api/v1/auth/login', [
            'email' => 'admin@galonku.com', 'password' => 'wrong',
        ])->assertUnauthorized()->assertFailed();
    }

    public function test_login_unknown_email_returns_401(): void
    {
        $this->post('/api/v1/auth/login', [
            'email' => 'ghost@test.com', 'password' => 'x',
        ])->assertUnauthorized();
    }

    public function test_login_updates_last_login(): void
    {
        $this->post('/api/v1/auth/login', [
            'email' => 'admin@galonku.com', 'password' => 'admin123',
        ]);
        $user = User::findByEmail('admin@galonku.com');
        $this->assertNotNull($user['last_login_at']);
    }


    public function test_me_requires_authentication(): void
    {
        $this->get('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_current_user_without_password(): void
    {
        $this->loginAsAdmin();
        $res = $this->get('/api/v1/auth/me', [], $this->withAuth());

        $res->assertOk()->assertSuccess();
        $res->assertJsonPath('data.email', 'admin@galonku.com');
        $this->assertArrayNotHasKey('password_hash', $res->json('data'));
    }

    public function test_me_rejects_invalid_token(): void
    {
        $this->get('/api/v1/auth/me', [], ['Authorization' => 'Bearer invalid.token.here'])
            ->assertUnauthorized();
    }


    public function test_refresh_returns_new_tokens(): void
    {
        $login = $this->post('/api/v1/auth/login', [
            'email' => 'admin@galonku.com', 'password' => 'admin123',
        ]);
        $refresh = $login->json('data.refresh_token');

        $res = $this->post('/api/v1/auth/refresh', ['refresh_token' => $refresh]);
        $res->assertOk()->assertSuccess();
        $this->assertNotEmpty($res->json('data.access_token'));
        $this->assertNotEmpty($res->json('data.refresh_token'));
    }

    public function test_refresh_rejects_access_token(): void
    {
        $login = $this->post('/api/v1/auth/login', [
            'email' => 'admin@galonku.com', 'password' => 'admin123',
        ]);
        $access = $login->json('data.access_token');

        $this->post('/api/v1/auth/refresh', ['refresh_token' => $access])
            ->assertUnauthorized();
    }

    public function test_refresh_rejects_garbage(): void
    {
        $this->post('/api/v1/auth/refresh', ['refresh_token' => 'garbage'])
            ->assertUnauthorized();
    }


    public function test_logout_blacklists_token(): void
    {
        $this->loginAsAdmin();
        $this->post('/api/v1/auth/logout', [], $this->withAuth())->assertOk();

        $this->get('/api/v1/auth/me', [], $this->withAuth())->assertUnauthorized();
    }

    public function test_logout_revokes_refresh_token(): void
    {
        $this->loginAsAdmin();
        $refresh = $this->post('/api/v1/auth/login', [
            'email' => 'admin@galonku.com', 'password' => 'admin123',
        ])->json('data.refresh_token');
    
        $this->post('/api/v1/auth/logout', ['refresh_token' => $refresh], $this->withAuth())
            ->assertOk();
    
        $this->post('/api/v1/auth/refresh', ['refresh_token' => $refresh])
            ->assertUnauthorized();
    }
}
