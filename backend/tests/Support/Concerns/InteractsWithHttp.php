<?php
declare(strict_types=1);

namespace Tests\Support\Concerns;

use App\Core\Auth;
use App\Core\Request;
use App\Exceptions\ErrorHandler;
use App\Testing\ResponseCaptured;
use Tests\Support\TestResponse;

trait InteractsWithHttp
{
    protected function get(string $uri, array $query = [], array $headers = []): TestResponse
    {
        return $this->call('GET', $uri, [], $query, $headers);
    }

    protected function post(string $uri, array $body = [], array $headers = []): TestResponse
    {
        return $this->call('POST', $uri, $body, [], $headers);
    }

    protected function put(string $uri, array $body = [], array $headers = []): TestResponse
    {
        return $this->call('PUT', $uri, $body, [], $headers);
    }

    protected function patch(string $uri, array $body = [], array $headers = []): TestResponse
    {
        return $this->call('PATCH', $uri, $body, [], $headers);
    }

    protected function delete(string $uri, array $headers = []): TestResponse
    {
        return $this->call('DELETE', $uri, [], [], $headers);
    }

    protected function call(
        string $method,
        string $uri,
        array $body = [],
        array $query = [],
        array $headers = []
    ): TestResponse {
        $request = Request::create($method, $uri, $body, $query, $headers);
        Auth::setRequest($request);

        try {
            $this->router->dispatch($request);
        } catch (ResponseCaptured $e) {
            return new TestResponse($e->status, $e->body, $e->headers);
        } catch (\Throwable $e) {
            // Tangkap exception dari controller (ValidationException, NotFoundException, dll)
            // dan lewatkan ErrorHandler seperti di production, lalu ambil Response-nya.
            try {
                ErrorHandler::handle($e);
            } catch (ResponseCaptured $captured) {
                return new TestResponse($captured->status, $captured->body, $captured->headers);
            }
        }

        return new TestResponse(200, '');
    }
}
