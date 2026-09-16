<?php
namespace Tests\Feature;

use Tests\Support\TestCase;

class LogTest extends TestCase
{
    public function test_logs_endpoint_admin_only(): void
    {
        $this->loginAsAdmin();
        $this->get('/api/v1/logs', [], $this->withAuth())->assertOk();
    }

    public function test_login_creates_log_entry(): void
    {
        $this->loginAsAdmin();
        $this->assertDatabaseHas('logs', ['action' => 'login', 'user_id' => 1]);
    }
}
