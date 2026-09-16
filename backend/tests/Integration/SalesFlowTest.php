<?php
namespace Tests\Integration;

use Tests\Support\TestCase;

class SalesFlowTest extends TestCase
{
    public function test_full_sales_flow(): void
    {
        $reg = $this->post('/api/v1/auth/register', [
            'name' => 'Pelanggan Baru',
            'email' => 'pelanggan.baru@test.com',
            'password' => 'password123',
            'phone' => '081355566677',
        ]);
        $reg->assertStatus(201);
        $newUserId = (int) $reg->json('data.id');

        $this->loginAsAdmin();

        $cust = $this->post('/api/v1/customers', [
            'name' => 'Toko Maju',
            'phone' => '0877111222333',
            'address' => 'Jl. Raya No. 99',
        ], $this->withAuth());
        $cust->assertStatus(201);
        $customerId = (int) $cust->json('data.id');

        $p1 = $this->get('/api/v1/products/1', [], $this->withAuth());
        $initialStock = (int) $p1->json('data.stock');

        $trx = $this->post('/api/v1/transactions', [
            'customer_id' => $customerId,
            'items' => [
                ['product_id' => 1, 'qty' => 2],
            ],
        ], $this->withAuth());
        $trx->assertStatus(201);
        $trxId = (int) $trx->json('data.id');
        $invoiceNo = $trx->json('data.invoice_no');
        $this->assertStringStartsWith('INV-', $invoiceNo);

        $p2 = $this->get('/api/v1/products/1', [], $this->withAuth());
        $this->assertSame($initialStock - 2, (int) $p2->json('data.stock'));

        $detail = $this->get("/api/v1/transactions/$trxId", [], $this->withAuth());
        $detail->assertOk();
        $this->assertCount(1, $detail->json('data.items'));
        $this->assertSame(2, (int) $detail->json('data.items.0.qty'));

        $this->put("/api/v1/transactions/$trxId/status", [
            'status' => 'paid',
            'paid_amount' => $detail->json('data.total_amount'),
        ], $this->withAuth())->assertOk();

        $this->assertDatabaseHas('transactions', ['id' => $trxId, 'status' => 'paid']);

        $this->assertDatabaseHas('logs', ['entity' => 'transaction', 'entity_id' => $trxId]);
    }

    public function test_login_logout_login_flow(): void
    {
        $login1 = $this->post('/api/v1/auth/login', [
            'email' => 'admin@galonku.com', 'password' => 'admin123',
        ]);
        $login1->assertOk();
        $token1 = $login1->json('data.access_token');

        $this->get('/api/v1/auth/me', [], ['Authorization' => "Bearer $token1"])
            ->assertOk();

        $this->post('/api/v1/auth/logout', [], ['Authorization' => "Bearer $token1"])
            ->assertOk();

        $this->get('/api/v1/auth/me', [], ['Authorization' => "Bearer $token1"])
            ->assertUnauthorized();

        $login2 = $this->post('/api/v1/auth/login', [
            'email' => 'admin@galonku.com', 'password' => 'admin123',
        ]);
        $login2->assertOk();
        $token2 = $login2->json('data.access_token');
        $this->assertNotSame($token1, $token2);

        $this->get('/api/v1/auth/me', [], ['Authorization' => "Bearer $token2"])
            ->assertOk();
    }

    public function test_transaction_rollback_on_insufficient_stock(): void
    {
        $this->loginAsAdmin();
        $c = $this->post('/api/v1/customers', [
            'name' => 'Cust', 'phone' => '0800111222',
        ], $this->withAuth());
        $cid = $c->json('data.id');

        $before = $this->get('/api/v1/products/1', [], $this->withAuth())->json('data.stock');

        $this->post('/api/v1/transactions', [
            'customer_id' => $cid,
            'items' => [
                ['product_id' => 1, 'qty' => 100000],
            ],
        ], $this->withAuth());

        $after = $this->get('/api/v1/products/1', [], $this->withAuth())->json('data.stock');
        $this->assertSame($before, $after);

        $this->assertDatabaseMissing('transactions', ['customer_id' => $cid]);
    }
}
