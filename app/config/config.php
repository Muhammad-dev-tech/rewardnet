<?php

declare(strict_types=1);

$env = static function (string $key, mixed $default = null): mixed {
    $value = getenv($key);
    if ($value === false || $value === null || $value === '') {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    return $value;
};

// Check for database connection URL (commonly set by Railway or other cloud providers)
$dbUrl = $env('MYSQL_URL') ?? $env('DATABASE_URL');
$parsedHost = null;
$parsedPort = null;
$parsedUser = null;
$parsedPass = null;
$parsedDb   = null;

if (!empty($dbUrl)) {
    $parts = parse_url((string) $dbUrl);
    if (is_array($parts)) {
        $parsedHost = $parts['host'] ?? null;
        $parsedPort = isset($parts['port']) ? (int) $parts['port'] : null;
        $parsedUser = $parts['user'] ?? null;
        $parsedPass = $parts['pass'] ?? null;
        $parsedDb   = isset($parts['path']) ? ltrim($parts['path'], '/') : null;
    }
}

return [
    'app_name' => 'RewardNet',
    'app_env' => $env('APP_ENV', 'production'),
    'db' => [
        'host' => $parsedHost ?? $env('DB_HOST') ?? $env('MYSQLHOST', '127.0.0.1'),
        'port' => $parsedPort ?? (int) ($env('DB_PORT') ?? $env('MYSQLPORT', '3306')),
        'database' => $parsedDb ?? $env('DB_DATABASE') ?? $env('MYSQLDATABASE', 'rewardnet'),
        'username' => $parsedUser ?? $env('DB_USERNAME') ?? $env('MYSQLUSER', 'root'),
        'password' => $parsedPass ?? $env('DB_PASSWORD') ?? $env('MYSQLPASSWORD', ''),
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
