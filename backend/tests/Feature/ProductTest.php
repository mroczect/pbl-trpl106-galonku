<?php
namespace Tests\Feature;

use Tests\Support\TestCase;

class ProductTest extends TestCase
{
    public function test_index_requires_auth(): void
    {
        $this->get('/api/v1/products')->assertUnauthorized();
    }

    public function test_index_returns_paginated_list(): void
    {
        $this->loginAsAdmin();
        $res = $this->get('/api/v1/products', [], $this->withAuth());

        $res->assertOk()->assertSuccess();
        $this->assertIsArray($res->json('data'));
        $this->assertGreaterThan(0, count($res->json('data')));
        $res->assertJsonHas('meta.total');
        $res->assertJsonHas('meta.page');
        $res->assertJsonHas('meta.total_pages');
    }

    public function test_index_filter_by_category(): void
    {
        $this->loginAsAdmin();
        $res = $this->get('/api/v1/products', ['category' => 'galon'], $this->withAuth());

        $res->assertOk();
        foreach ($res->json('data') as $p) {
            $this->assertSame('galon', $p['category']);
        }
    }

    public function test_show_returns_product(): void
    {
        $this->loginAsAdmin();
        $res = $this->get('/api/v1/products/1', [], $this->withAuth());
        $res->assertOk()->assertSuccess();
        $this->assertNotNull($res->json('data.sku'));
    }

    public function test_show_404_for_unknown(): void
    {
        $this->loginAsAdmin();
        $this->get('/api/v1/products/99999', [], $this->withAuth())->assertNotFound();
    }

    public function test_low_stock_endpoint(): void
    {
        $this->loginAsAdmin();
        $res = $this->get('/api/v1/products/low-stock', ['threshold' => 25], $this->withAuth());
        $res->assertOk()->assertSuccess();
        $this->assertIsArray($res->json('data'));
    }

    public function test_admin_can_create_product(): void
    {
        $this->loginAsAdmin();
        $res = $this->post('/api/v1/products', [
            'sku'      => 'NEW-SKU-01',
            'name'     => 'Produk Baru',
            'price'    => 15000,
            'category' => 'aksesoris',
            'stock'    => 10,
        ], $this->withAuth());

        $res->assertStatus(201)->assertSuccess();
        $this->assertDatabaseHas('products', ['sku' => 'NEW-SKU-01']);
    }

    public function test_create_rejects_duplicate_sku(): void
    {
        $this->loginAsAdmin();
        $this->post('/api/v1/products', [
            'sku'   => 'GLN-AQUA-19L',
            'name'  => 'Dup',
            'price' => 1000,
        ], $this->withAuth())->assertStatus(409);
    }

    public function test_create_validates_payload(): void
    {
        $this->loginAsAdmin();
        $this->post('/api/v1/products', [
            'sku' => 'X', 'name' => 'Y',
        ], $this->withAuth())->assertUnprocessable();
    }

    public function test_admin_can_update_product(): void
    {
        $this->loginAsAdmin();
        $res = $this->put('/api/v1/products/1', [
            'name' => 'Nama Baru',
            'price' => 25000,
        ], $this->withAuth());

        $res->assertOk();
        $this->assertDatabaseHas('products', ['id' => 1, 'name' => 'Nama Baru']);
    }

    public function test_admin_can_soft_delete_product(): void
    {
        $this->loginAsAdmin();
        $this->delete('/api/v1/products/1', $this->withAuth())->assertOk();
        $this->assertDatabaseHas('products', ['id' => 1, 'is_active' => 0]);
    }
}
