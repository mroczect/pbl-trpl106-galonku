<?php
return [
    'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*'),
    'allowed_methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
    'allowed_headers' => 'Content-Type, Authorization, X-Requested-With',
    'credentials'     => true,
];
