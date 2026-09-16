<?php
namespace Tests\Unit;

use App\Core\Request;
use Tests\Support\UnitTestCase;

class RequestTest extends UnitTestCase
{
    public function test_create_factory(): void
    {
        $r = Request::create('POST', '/api/v1/test', ['x' => 1], ['y' => 2]);
        $this->assertSame('POST', $r->method());
        $this->assertSame('/api/v1/test', $r->path());
        $this->assertSame(1, $r->body('x'));
        $this->assertSame(2, $r->query('y'));
    }

    public function test_all_merges_query_and_body(): void
    {
        $r = Request::create('POST', '/x', ['a' => 1], ['b' => 2]);
        $this->assertSame(['b' => 2, 'a' => 1], $r->all());
    }

    public function test_bearer_token_extracted(): void
    {
        $r = Request::create('GET', '/x', [], [], ['Authorization' => 'Bearer abc.def.ghi']);
        $this->assertSame('abc.def.ghi', $r->bearerToken());
    }

    public function test_bearer_token_case_insensitive(): void
    {
        $r = Request::create('GET', '/x', [], [], ['Authorization' => 'bearer xyz']);
        $this->assertSame('xyz', $r->bearerToken());
    }

    public function test_bearer_token_null_when_absent(): void
    {
        $r = Request::create('GET', '/x');
        $this->assertNull($r->bearerToken());
    }

    public function test_header_lookup_case_insensitive(): void
    {
        $r = Request::create('GET', '/x', [], [], ['X-Custom' => 'value']);
        $this->assertSame('value', $r->header('x-custom'));
        $this->assertSame('value', $r->header('X-CUSTOM'));
    }
}
