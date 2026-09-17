<?php
namespace Tests\Feature;

use Tests\Support\TestCase;

class CustomerTest extends TestCase
{
    public function test_index(): void
    {
        $this->loginAsAdmin();
        $this->get('/api/v1/customers', [], $this->withAuth())
            ->assertOk()->assertSuccess();
    }

    public function test_store_creates_customer(): void
    {
        $this->loginAsAdmin();
        $res = $this->post('/api/v1/customers', [
            'name'    => 'Ibu Siti',
            'phone'   => '0899111222',
            'address' => 'Jl. Merdeka No. 1',
        ], $this->withAuth());

        $res->assertStatus(201)->assertSuccess();
        $this->assertDatabaseHas('customers', ['phone' => '0899111222']);
    }

    public function test_store_rejects_duplicate_phone(): void
    {
        $this->loginAsAdmin();
        $this->post('/api/v1/customers', [
            'name' => 'Cust A', 'phone' => '0899111222',
        ], $this->withAuth())->assertStatus(201);

        $this->post('/api/v1/customers', [
            'name' => 'Cust B', 'phone' => '0899111222',
        ], $this->withAuth())->assertStatus(409);
    }

    public function test_store_validates(): void
    {
        $this->loginAsAdmin();
        $this->post('/api/v1/customers', ['name' => 'A'], $this->withAuth())
            ->assertUnprocessable();
    }

    public function test_show_404(): void
    {
        $this->loginAsAdmin();
        $this->get('/api/v1/customers/99999', [], $this->withAuth())->assertNotFound();
    }

    public function test_update(): void
    {
        $this->loginAsAdmin();
        $create = $this->post('/api/v1/customers', [
            'name' => 'Cust', 'phone' => '0800000001',
        ], $this->withAuth());
        $id = $create->json('data.id');

        $this->put("/api/v1/customers/$id", ['name' => 'Updated'], $this->withAuth())
            ->assertOk();
        $this->assertDatabaseHas('customers', ['id' => $id, 'name' => 'Updated']);
    }

    public function test_destroy_soft_deletes(): void
    {
        $this->loginAsAdmin();
        $create = $this->post('/api/v1/customers', [
            'name' => 'Cust', 'phone' => '0800000002',
        ], $this->withAuth());
        $id = $create->json('data.id');

        $this->delete("/api/v1/customers/$id", $this->withAuth())->assertOk();
        $this->assertDatabaseHas('customers', ['id' => $id, 'is_active' => 0]);
    }
}
