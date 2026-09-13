<?php
return [
    'name'     => $_ENV['APP_NAME'] ?? 'Galonku API',
    'env'      => $_ENV['APP_ENV'] ?? 'production',
    'debug'    => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Jakarta',
    'version'  => '0.1.0',
];
