<?php

declare(strict_types=1);

require __DIR__ . '/../app/core/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/AuthService.php';

$email = 'rewardnet_test_' . time() . '@example.com';
$auth = new AuthService();

try {
    $id = $auth->register('Auth Test User', $email, 'password123');

    $session = $auth->login($email, 'password123');
    $auth->logout();

    echo "REGISTERED:$id\n";
    echo "LOGIN_OK:{$session['id']}\n";
    echo "LOGOUT_OK\n";
} catch (Throwable $e) {
    echo 'ERROR:' . $e->getMessage() . '\n';
}
