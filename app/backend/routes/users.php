<?php
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;

$router->get('/api/users',          [UserController::class, 'index'],   [AuthMiddleware::class]);
$router->get('/api/users/{id}',     [UserController::class, 'show'],    [AuthMiddleware::class]);
$router->put('/api/users/{id}',     [UserController::class, 'update'],  [AuthMiddleware::class]);
$router->delete('/api/users/{id}',  [UserController::class, 'destroy'], [AuthMiddleware::class]);
