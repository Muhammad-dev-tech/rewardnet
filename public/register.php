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

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $auth->register(
            $_POST['full_name'] ?? '',
            $_POST['email'] ?? '',
            $_POST['password'] ?? ''
        );
        header('Location: /dashboard.php');
        exit;
    } catch (Throwable $e) {
        $message = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RewardNet - Register</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            <p class="eyebrow">RewardNet</p>
            <h1>Create account</h1>
            <?php if ($message !== ''): ?>
                <div class="flash"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <form method="post" class="auth-form">
                <label>
                    Full name
                    <input type="text" name="full_name" required>
                </label>
                <label>
                    Email
                    <input type="email" name="email" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" required minlength="6">
                </label>
                <button type="submit">Register</button>
            </form>
            <p class="auth-link">Already have an account? <a href="/login.php">Login</a></p>
        </div>
    </div>
</body>
</html>
