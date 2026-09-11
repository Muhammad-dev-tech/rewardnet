<?php

declare(strict_types=1);

final class AuthService
{
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['rewardnet_user_id']) && !empty($_SESSION['rewardnet_user_id']);
    }

    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $password = trim($password);

        if ($email === '' || $password === '') {
            throw new InvalidArgumentException('Email and password are required.');
        }

        $config = require __DIR__ . '/../config/config.php';
        $adminEmail = strtolower(trim((string) ($config['admin']['email'] ?? '')));
        $adminPassword = (string) ($config['admin']['password'] ?? '');
        $adminFullName = (string) ($config['admin']['full_name'] ?? 'RewardNet Admin');

        if ($email === $adminEmail && $password === $adminPassword) {
            $pdo = Database::getConnection();
            $check = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            $existingAdmin = $check->fetch();

            if (!$existingAdmin) {
                $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
                $create = $pdo->prepare(
                    'INSERT INTO users (full_name, email, password_hash, role, points) VALUES (?, ?, ?, "admin", 0)'
                );
                $create->execute([$adminFullName, $email, $hash]);
                $existingAdmin = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
                $existingAdmin->execute([$email]);
                $existingAdmin = $existingAdmin->fetch();
            } else {
                $pdo->prepare('UPDATE users SET role = "admin" WHERE email = ?')->execute([$email]);
                $refresh = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
                $refresh->execute([$email]);
                $existingAdmin = $refresh->fetch();
            }

            $user = $existingAdmin;
        } else {
            $stmt = Database::getConnection()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                throw new RuntimeException('Invalid email or password.');
            }
        }

        $_SESSION['rewardnet_user_id'] = (int) $user['id'];
        $_SESSION['rewardnet_user_name'] = $user['full_name'];
        $_SESSION['rewardnet_user_role'] = $user['role'];

        return [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
        ];
    }

    public function register(string $fullName, string $email, string $password): int
    {
        $fullName = trim($fullName);
        $email = strtolower(trim($email));
        $password = trim($password);

        if ($fullName === '' || $email === '' || $password === '') {
            throw new InvalidArgumentException('Name, email, and password are required.');
        }

        if (strlen($password) < 6) {
            throw new InvalidArgumentException('Password must be at least 6 characters long.');
        }

        $config = require __DIR__ . '/../config/config.php';
        $adminEmail = strtolower(trim((string) ($config['admin']['email'] ?? '')));
        if ($email === $adminEmail) {
            throw new RuntimeException('This email is reserved for the platform administrator.');
        }

        $pdo = Database::getConnection();
        $existing = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $existing->execute([$email]);
        if ($existing->fetch()) {
            throw new RuntimeException('An account with this email already exists.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, points) VALUES (?, ?, ?, ?, 0)');
        $stmt->execute([$fullName, $email, $hash, 'member']);

        $userId = (int) $pdo->lastInsertId();
        $user = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $user->execute([$userId]);
        $createdUser = $user->fetch();

        if ($createdUser) {
            $_SESSION['rewardnet_user_id'] = (int) $createdUser['id'];
            $_SESSION['rewardnet_user_name'] = $createdUser['full_name'];
            $_SESSION['rewardnet_user_role'] = $createdUser['role'];
        }

        return $userId;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }

    public function requireAdmin(): void
    {
        $this->requireLogin();
        if (!isset($_SESSION['rewardnet_user_role']) || $_SESSION['rewardnet_user_role'] !== 'admin') {
            header('Location: /dashboard.php');
            exit;
        }
    }
}
