<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Response;
use Dotenv\Dotenv;

$envFile = file_exists(__DIR__ . '/../.env.testing') ? '.env.testing' : '.env';
Dotenv::createImmutable(__DIR__ . '/..', $envFile)->safeLoad();

date_default_timezone_set('Asia/Jakarta');

Response::$testMode = true;
