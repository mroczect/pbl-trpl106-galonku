<?php
use App\Controllers\ScheduleController;
use App\Middleware\AuthMiddleware;

$router->get('/api/schedules',             [ScheduleController::class, 'index'],        [AuthMiddleware::class]);
$router->get('/api/schedules/{id}',        [ScheduleController::class, 'show'],         [AuthMiddleware::class]);
$router->post('/api/schedules',            [ScheduleController::class, 'store'],        [AuthMiddleware::class]);
$router->put('/api/schedules/{id}/status', [ScheduleController::class, 'updateStatus'], [AuthMiddleware::class]);
