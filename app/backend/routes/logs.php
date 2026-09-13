<?php
use App\Controllers\LogController;
use App\Middleware\AuthMiddleware;

$router->get('/api/logs', [LogController::class, 'index'], [AuthMiddleware::class]);
