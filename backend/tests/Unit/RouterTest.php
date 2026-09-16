<?php
namespace Tests\Unit;

use App\Core\Request;
use App\Core\Router;
use App\Testing\ResponseCaptured;
use Tests\Support\UnitTestCase;

class RouterTest extends UnitTestCase
{
    public function test_simple_route_dispatches(): void
    {
        $router = new Router();
        $router->get('/hello', fn() => \App\Core\Response::success(['msg' => 'hi']));

        $this->expectException(ResponseCaptured::class);
        try { $router->dispatch(Request::create('GET', '/hello')); }
        catch (ResponseCaptured $e) {
            $this->assertSame(200, $e->status);
            $this->assertStringContainsString('"msg":"hi"', $e->body);
            throw $e;
        }
    }

    public function test_route_param_passed(): void
    {
        $router = new Router();
        $router->get('/users/{id}', fn($req, $id) => \App\Core\Response::success(['id' => $id]));

        $this->expectException(ResponseCaptured::class);
        try { $router->dispatch(Request::create('GET', '/users/42')); }
        catch (ResponseCaptured $e) {
            $this->assertSame(200, $e->status);
            $this->assertStringContainsString('"id":"42"', $e->body);
            throw $e;
        }
    }

    public function test_404_when_no_route(): void
    {
        $router = new Router();
        $this->expectException(ResponseCaptured::class);
        try { $router->dispatch(Request::create('GET', '/nope')); }
        catch (ResponseCaptured $e) {
            $this->assertSame(404, $e->status);
            throw $e;
        }
    }

    public function test_405_when_method_mismatch(): void
    {
        $router = new Router();
        $router->get('/x', fn() => null);

        $this->expectException(ResponseCaptured::class);
        try { $router->dispatch(Request::create('POST', '/x')); }
        catch (ResponseCaptured $e) {
            $this->assertSame(405, $e->status);
            throw $e;
        }
    }

    public function test_group_prefix_applied(): void
    {
        $router = new Router();
        $router->group(['prefix' => '/api/v1'], function ($r) {
            $r->get('/ping', fn() => \App\Core\Response::success(['ok' => true]));
        });

        $this->expectException(ResponseCaptured::class);
        try { $router->dispatch(Request::create('GET', '/api/v1/ping')); }
        catch (ResponseCaptured $e) {
            $this->assertSame(200, $e->status);
            throw $e;
        }
    }
}
