<?php
/** @var \App\Core\Router $router */

require __DIR__ . '/../routes/auth.php';
require __DIR__ . '/../routes/users.php';
require __DIR__ . '/../routes/roles.php';
require __DIR__ . '/../routes/products.php';
require __DIR__ . '/../routes/transactions.php';
require __DIR__ . '/../routes/schedules.php';
require __DIR__ . '/../routes/logs.php';

// Health check
$router->get('/', function () {
    \App\Core\Response::success([
        'app'     => $_ENV['APP_NAME'] ?? 'Galonku API',
        'version' => '0.1.0',
        'status'  => 'running',
    ]);
});
