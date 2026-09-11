<?php

declare(strict_types=1);

$env = static function (string $key, mixed $default = null): mixed {
    $value = getenv($key);
    if ($value === false || $value === null || $value === '') {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    return $value;
};

return [
    'app_name' => 'RewardNet',
    'app_env' => $env('APP_ENV', 'production'),
    'db' => [
        'host' => $env('DB_HOST', '127.0.0.1'),
        'database' => $env('DB_DATABASE', 'rewardnet'),
        'username' => $env('DB_USERNAME', 'root'),
        'password' => $env('DB_PASSWORD', ''),
        'charset' => $env('DB_CHARSET', 'utf8mb4'),
    ],
    'admin' => [
        'email' => $env('ADMIN_EMAIL', 'admin@rewardnet.local'),
        'password' => $env('ADMIN_PASSWORD', 'Admin123!'),
        'full_name' => $env('ADMIN_FULL_NAME', 'RewardNet Admin'),
    ],
    'site' => [
        'base_url' => $env('APP_URL', 'http://127.0.0.1:8000'),
    ],
];
