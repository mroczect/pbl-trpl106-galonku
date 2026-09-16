<?php
namespace Tests\Feature;

use Tests\Support\TestCase;

class UserAndRoleTest extends TestCase
{
    public function test_list_users_admin_only(): void
    {
        $this->loginAsAdmin();
        $res = $this->get('/api/v1/users', [], $this->withAuth());
        $res->assertOk()->assertSuccess();
        $this->assertGreaterThanOrEqual(3, count($res->json('data')));
    }

    public function test_show_user(): void
    {
        $this->loginAsAdmin();
        $res = $this->get('/api/v1/users/1', [], $this->withAuth());
        $res->assertOk();
        $this->assertArrayNotHasKey('password_hash', $res->json('data'));
    }

    public function test_update_user(): void
    {
        $this->loginAsAdmin();
        $this->put('/api/v1/users/2', ['name' => 'Kurir Baru'], $this->withAuth())->assertOk();
        $this->assertDatabaseHas('users', ['id' => 2, 'name' => 'Kurir Baru']);
    }

    public function test_destroy_soft_deletes_user(): void
    {
        $this->loginAsAdmin();
        $this->delete('/api/v1/users/3', $this->withAuth())->assertOk();
        $this->assertDatabaseHas('users', ['id' => 3, 'is_active' => 0]);
    }

    public function test_list_roles(): void
    {
        $this->loginAsAdmin();
        $res = $this->get('/api/v1/roles', [], $this->withAuth());
        $res->assertOk()->assertSuccess();
        $this->assertCount(3, $res->json('data'));
    }
}
