<?php
use App\Controllers\ProductController;
use App\Middleware\AuthMiddleware;

$router->get('/api/products',            [ProductController::class, 'index']);
$router->get('/api/products/low-stock',  [ProductController::class, 'lowStock']);
$router->get('/api/products/{id}',       [ProductController::class, 'show']);
$router->post('/api/products',           [ProductController::class, 'store'],    [AuthMiddleware::class]);
$router->put('/api/products/{id}',       [ProductController::class, 'update'],   [AuthMiddleware::class]);
$router->delete('/api/products/{id}',    [ProductController::class, 'destroy'],  [AuthMiddleware::class]);
