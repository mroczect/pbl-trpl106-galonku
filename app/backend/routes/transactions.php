<?php
use App\Controllers\TransactionController;
use App\Middleware\AuthMiddleware;

$router->get('/api/transactions',             [TransactionController::class, 'index'],        [AuthMiddleware::class]);
$router->get('/api/transactions/{id}',        [TransactionController::class, 'show'],         [AuthMiddleware::class]);
$router->post('/api/transactions',            [TransactionController::class, 'store'],        [AuthMiddleware::class]);
$router->put('/api/transactions/{id}/status', [TransactionController::class, 'updateStatus'], [AuthMiddleware::class]);
