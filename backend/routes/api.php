<?php

use App\Core\Router;
use App\Controllers\Api\V1\{
    AuthController,
    UserController,
    RoleController,
    ProductController,
    CustomerController,
    TransactionController,
    ScheduleController,
    LogController
};
use App\Middleware\{AuthMiddleware, RoleMiddleware, RateLimitMiddleware};

$router->group(['prefix' => '/api/v1'], function (Router $router) {

	$router->post('/auth/register', [AuthController::class, 'register'],
    [RateLimitMiddleware::class . ':register:5:3600']);
	$router->post('/auth/login',    [AuthController::class, 'login'],
    [RateLimitMiddleware::class . ':login:5:60']);
	$router->post('/auth/refresh',  [AuthController::class, 'refresh'],
    [RateLimitMiddleware::class . ':refresh:20:60']);

    $router->group(['middleware' => [AuthMiddleware::class]], function (Router $router) {

        $router->get('/auth/me',      [AuthController::class, 'me']);
        $router->post('/auth/logout', [AuthController::class, 'logout']);

        $router->get('/products',           [ProductController::class, 'index']);
        $router->get('/products/low-stock', [ProductController::class, 'lowStock']);
        $router->get('/products/{id}',      [ProductController::class, 'show']);

        $router->get('/customers',      [CustomerController::class, 'index']);
        $router->get('/customers/{id}', [CustomerController::class, 'show']);
        $router->post('/customers',     [CustomerController::class, 'store']);
        $router->put('/customers/{id}', [CustomerController::class, 'update']);

        $router->get('/transactions',             [TransactionController::class, 'index']);
        $router->get('/transactions/{id}',        [TransactionController::class, 'show']);
        $router->post('/transactions',            [TransactionController::class, 'store']);
        $router->put('/transactions/{id}/status', [TransactionController::class, 'updateStatus']);

        $router->get('/schedules',             [ScheduleController::class, 'index']);
        $router->get('/schedules/{id}',        [ScheduleController::class, 'show']);
        $router->post('/schedules',            [ScheduleController::class, 'store']);
        $router->put('/schedules/{id}/status', [ScheduleController::class, 'updateStatus']);

        $router->group(['middleware' => [RoleMiddleware::class . ':admin']], function (Router $router) {

            $router->get('/users',         [UserController::class, 'index']);
            $router->get('/users/{id}',    [UserController::class, 'show']);
            $router->put('/users/{id}',    [UserController::class, 'update']);
            $router->delete('/users/{id}', [UserController::class, 'destroy']);

            $router->get('/roles', [RoleController::class, 'index']);

            $router->post('/products',        [ProductController::class, 'store']);
            $router->put('/products/{id}',    [ProductController::class, 'update']);
            $router->delete('/products/{id}', [ProductController::class, 'destroy']);

            $router->delete('/customers/{id}', [CustomerController::class, 'destroy']);

            $router->get('/logs', [LogController::class, 'index']);
        });
    });
});

$router->get('/', function () {
    \App\Core\Response::success([
        'app'     => $_ENV['APP_NAME'] ?? 'Galonku API',
        'version' => '1.0.0',
        'status'  => 'running',
        'time'    => date('c'),
    ]);
});
