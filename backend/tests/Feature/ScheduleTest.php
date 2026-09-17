<?php
namespace Tests\Feature;

use Tests\Support\TestCase;

class ScheduleTest extends TestCase
{
    public function test_index(): void
    {
        $this->loginAsAdmin();
        $this->get('/api/v1/schedules', [], $this->withAuth())
            ->assertOk()->assertSuccess();
    }

    public function test_create_schedule(): void
    {
        $this->loginAsAdmin();

        $c = $this->post('/api/v1/customers', [
            'name' => 'Pel A', 'phone' => '0812000000',
        ], $this->withAuth());
        $customerId = $c->json('data.id');

        $res = $this->post('/api/v1/schedules', [
            'customer_id'  => $customerId,
            'user_id'      => 1,
            'scheduled_at' => date('Y-m-d H:i:s', time() + 3600),
        ], $this->withAuth());

        $res->assertStatus(201)->assertSuccess();
        $this->assertDatabaseHas('schedules', ['customer_id' => $customerId, 'status' => 'pending']);
    }

    public function test_create_validates(): void
    {
        $this->loginAsAdmin();
        $this->post('/api/v1/schedules', [], $this->withAuth())
            ->assertUnprocessable();
    }

    public function test_update_status(): void
    {
        $this->loginAsAdmin();
        $c = $this->post('/api/v1/customers', ['name' => 'Pel A', 'phone' => '0812999999'], $this->withAuth());
        $cid = $c->json('data.id');

        $create = $this->post('/api/v1/schedules', [
            'customer_id'  => $cid,
            'user_id'      => 1,
            'scheduled_at' => date('Y-m-d H:i:s'),
        ], $this->withAuth());
        $id = $create->json('data.id');

        $this->put("/api/v1/schedules/$id/status", ['status' => 'on_route'], $this->withAuth())
            ->assertOk();
        $this->assertDatabaseHas('schedules', ['id' => $id, 'status' => 'on_route']);
    }

    public function test_update_status_rejects_invalid(): void
    {
        $this->loginAsAdmin();
        $c = $this->post('/api/v1/customers', ['name' => 'Pel B', 'phone' => '0812888888'], $this->withAuth());
        $create = $this->post('/api/v1/schedules', [
            'customer_id'  => $c->json('data.id'),
            'user_id'      => 1,
            'scheduled_at' => date('Y-m-d H:i:s'),
        ], $this->withAuth());
        $id = $create->json('data.id');

        $this->put("/api/v1/schedules/$id/status", ['status' => 'wrong'], $this->withAuth())
            ->assertUnprocessable();
    }
}
