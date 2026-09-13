<?php
use App\Controllers\RoleController;

$router->get('/api/roles', [RoleController::class, 'index']);
