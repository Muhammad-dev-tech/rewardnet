<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

define('APP_NAME', $config['app_name']);
define('APP_ENV', $config['app_env']);
define('BASE_URL', $config['site']['base_url']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
