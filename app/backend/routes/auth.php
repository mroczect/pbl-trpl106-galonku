<?php
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;

$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login',    [AuthController::class, 'login']);
$router->get('/api/auth/me',        [AuthController::class, 'me'],     [AuthMiddleware::class]);
$router->post('/api/auth/logout',   [AuthController::class, 'logout'], [AuthMiddleware::class]);
