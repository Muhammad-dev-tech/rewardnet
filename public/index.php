<?php

declare(strict_types=1);

require __DIR__ . '/../app/core/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/AuthService.php';

$auth = new AuthService();
if ($auth->isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= APP_NAME ?> - Rewards by Action</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="landing-shell">
        <div class="landing-card">
            <p class="eyebrow">Watch. Earn. Connect.</p>
            <h1>RewardNet</h1>
            <p class="subtitle">A simulated loyalty platform where users watch reward advertisements and earn virtual data balance.</p>

            <div class="cta-row">
                <a class="primary-btn" href="/register.php">Create account</a>
                <a class="secondary-btn" href="/login.php">Login</a>
            </div>

            <div class="feature-list">
                <div><strong>Users</strong><span>Register and manage accounts</span></div>
                <div><strong>Rewards</strong><span>Earn virtual data by ad completion</span></div>
                <div><strong>Experience</strong><span>Track points, redeem rewards, and stay engaged</span></div>
            </div>
        </div>
    </div>
</body>
</html>
