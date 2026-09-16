<?php
namespace Tests\Feature;

use Tests\Support\TestCase;

class AuthorizationTest extends TestCase
{
    public function test_pelanggan_cannot_access_admin_routes(): void
    {
        $this->loginAsPelanggan();
        $this->get('/api/v1/users', [], $this->withAuth())->assertForbidden();
        $this->get('/api/v1/logs', [], $this->withAuth())->assertForbidden();
    }

    public function test_kurir_cannot_create_product(): void
    {
        $this->loginAsKurir();
        $this->post('/api/v1/products', [
            'sku' => 'X-1', 'name' => 'Test', 'price' => 1000,
        ], $this->withAuth())->assertForbidden();
    }

    public function test_admin_can_access_users(): void
    {
        $this->loginAsAdmin();
        $this->get('/api/v1/users', [], $this->withAuth())->assertOk();
    }

    public function test_kurir_can_list_products(): void
    {
        $this->loginAsKurir();
        $this->get('/api/v1/products', [], $this->withAuth())->assertOk();
    }

    public function test_unauthenticated_blocked_on_protected_route(): void
    {
        $this->get('/api/v1/products')->assertUnauthorized();
    }
}
