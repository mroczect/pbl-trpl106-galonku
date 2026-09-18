<?php
declare(strict_types=1);

use App\Core\App;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$shellSecret = getenv('JWT_SECRET') ?: null;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$jwtSecret = $shellSecret
    ?? $_SERVER['JWT_SECRET']
    ?? $_ENV['JWT_SECRET']
    ?? '';

if (strlen($jwtSecret) < 32) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server misconfigured']);
    exit;
}

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Jakarta');

App::boot()->run();
