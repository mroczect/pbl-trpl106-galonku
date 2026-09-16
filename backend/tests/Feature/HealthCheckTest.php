<?php
namespace Tests\Feature;

use Tests\Support\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_root_returns_health_info(): void
    {
        $res = $this->get('/');
        $res->assertOk()->assertSuccess();
        $res->assertJsonPath('data.status', 'running');
    }
}
