<?php
namespace Tests\Feature;

use Tests\Support\TestCase;

class TransactionTest extends TestCase
{
    protected function seedCustomer(): int
    {
        $res = $this->post('/api/v1/customers', [
            'name' => 'Pelanggan A', 'phone' => '0811110000',
        ], $this->withAuth());
        return (int) $res->json('data.id');
    }

    public function test_index_requires_auth(): void
    {
        $this->get('/api/v1/transactions')->assertUnauthorized();
    }

    public function test_create_transaction_reduces_stock(): void
    {
        $this->loginAsAdmin();
        $customerId = $this->seedCustomer();

        $res = $this->post('/api/v1/transactions', [
            'customer_id' => $customerId,
            'items' => [
                ['product_id' => 1, 'qty' => 3],
            ],
        ], $this->withAuth());

        $res->assertStatus(201)->assertSuccess();
        $this->assertNotNull($res->json('data.invoice_no'));
        $this->assertSame(1, (int) $res->json('data.items_count'));

        $this->assertDatabaseHas('products', ['id' => 1, 'stock' => 46]);
    }

    public function test_create_transaction_with_multiple_items(): void
    {
        $this->loginAsAdmin();
        $customerId = $this->seedCustomer();

        $res = $this->post('/api/v1/transactions', [
            'customer_id' => $customerId,
            'items' => [
                ['product_id' => 1, 'qty' => 1],
                ['product_id' => 2, 'qty' => 2],
            ],
        ], $this->withAuth());

        $res->assertStatus(201);
        $this->assertSame(2, (int) $res->json('data.items_count'));
    }

    public function test_create_fails_when_stock_insufficient(): void
    {
        $this->loginAsAdmin();
        $customerId = $this->seedCustomer();

        $this->post('/api/v1/transactions', [
            'customer_id' => $customerId,
            'items' => [
                ['product_id' => 1, 'qty' => 99999],
            ],
        ], $this->withAuth())->assertStatus(500); 

        $this->assertDatabaseHas('products', ['id' => 1, 'stock' => 49]);
    }

    public function test_create_validates_required_items(): void
    {
        $this->loginAsAdmin();
        $this->post('/api/v1/transactions', [
            'customer_id' => 1,
        ], $this->withAuth())->assertUnprocessable();
    }

    public function test_show_returns_transaction_with_items(): void
    {
        $this->loginAsAdmin();
        $customerId = $this->seedCustomer();

        $create = $this->post('/api/v1/transactions', [
            'customer_id' => $customerId,
            'items' => [['product_id' => 1, 'qty' => 1]],
        ], $this->withAuth());
        $id = $create->json('data.id');

        $res = $this->get("/api/v1/transactions/$id", [], $this->withAuth());
        $res->assertOk();
        $this->assertIsArray($res->json('data.items'));
        $this->assertNotEmpty($res->json('data.items'));
    }

    public function test_update_status(): void
    {
        $this->loginAsAdmin();
        $customerId = $this->seedCustomer();

        $create = $this->post('/api/v1/transactions', [
            'customer_id' => $customerId,
            'items' => [['product_id' => 1, 'qty' => 1]],
        ], $this->withAuth());
        $id = $create->json('data.id');

        $res = $this->put("/api/v1/transactions/$id/status", [
            'status' => 'paid',
            'paid_amount' => 20000,
        ], $this->withAuth());

        $res->assertOk();
        $this->assertDatabaseHas('transactions', ['id' => $id, 'status' => 'paid']);
    }

    public function test_update_status_rejects_invalid_value(): void
    {
        $this->loginAsAdmin();
        $this->put('/api/v1/transactions/1/status', ['status' => 'hacked'], $this->withAuth())
            ->assertUnprocessable();
    }
}
