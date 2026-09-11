<?php

declare(strict_types=1);

final class RewardService
{
    public function getUsers(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM users ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public function getRewards(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM rewards ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public function getTransactions(int $limit = 10): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT t.*, u.full_name AS user_name
             FROM transactions t
             INNER JOIN users u ON u.id = t.user_id
             ORDER BY t.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function registerMember(string $fullName, string $email, string $role = 'member'): int
    {
        $fullName = trim($fullName);
        $email = strtolower(trim($email));
        $role = strtolower(trim($role));

        if ($fullName === '' || $email === '') {
            throw new InvalidArgumentException('Full name and email are required.');
        }

        $config = require __DIR__ . '/../config/config.php';
        $adminEmail = strtolower(trim((string) ($config['admin']['email'] ?? '')));
        if ($email === $adminEmail) {
            throw new InvalidArgumentException('This email is reserved for the platform administrator.');
        }

        if (!in_array($role, ['admin', 'member'], true)) {
            $role = 'member';
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, role, points) VALUES (?, ?, ?, 0)');
        $stmt->execute([$fullName, $email, $role]);

        return (int) $pdo->lastInsertId();
    }

    public function addReward(string $title, string $description, int $pointsRequired): int
    {
        $title = trim($title);
        $description = trim($description);

        if ($title === '' || $pointsRequired <= 0) {
            throw new InvalidArgumentException('Reward title and required points are required.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO rewards (title, description, points_required, is_active) VALUES (?, ?, ?, 1)');
        $stmt->execute([$title, $description, $pointsRequired]);

        return (int) $pdo->lastInsertId();
    }

    public function awardPoints(int $userId, int $points, string $description): array
    {
        if ($points <= 0) {
            throw new InvalidArgumentException('Points awarded must be greater than zero.');
        }

        $user = $this->getUserById($userId);
        $pdo = Database::getConnection();

        $pdo->beginTransaction();
        try {
            $update = $pdo->prepare('UPDATE users SET points = points + ? WHERE id = ?');
            $update->execute([$points, $userId]);

            $insert = $pdo->prepare(
                'INSERT INTO transactions (user_id, type, points, description) VALUES (?, ?, ?, ?)'
            );
            $insert->execute([$userId, 'earn', $points, $description ?: 'Points awarded by admin']);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $updatedUser = $this->getUserById($userId);

        return [
            'user_id' => $userId,
            'previous_points' => (int) $user['points'],
            'new_points' => (int) $updatedUser['points'],
        ];
    }

    public function redeemReward(int $userId, int $rewardId): array
    {
        $user = $this->getUserById($userId);
        $reward = $this->getRewardById($rewardId);

        if ((int) $reward['is_active'] !== 1) {
            throw new RuntimeException('This reward is not active.');
        }

        if ((int) $user['points'] < (int) $reward['points_required']) {
            throw new RuntimeException('User does not have enough points for this reward.');
        }

        $pointsRequired = (int) $reward['points_required'];
        $pdo = Database::getConnection();

        $pdo->beginTransaction();
        try {
            $update = $pdo->prepare('UPDATE users SET points = points - ? WHERE id = ?');
            $update->execute([$pointsRequired, $userId]);

            $insert = $pdo->prepare(
                'INSERT INTO transactions (user_id, type, points, description) VALUES (?, ?, ?, ?)' 
            );
            $insert->execute([$userId, 'redeem', -$pointsRequired, 'Redeemed reward: ' . $reward['title']]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $updatedUser = $this->getUserById($userId);

        return [
            'user_id' => $userId,
            'reward_id' => $rewardId,
            'reward_title' => $reward['title'],
            'points_spent' => $pointsRequired,
            'remaining_points' => (int) $updatedUser['points'],
        ];
    }

    public function getUserById(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        return $user;
    }

    public function getRewardById(int $rewardId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM rewards WHERE id = ?');
        $stmt->execute([$rewardId]);
        $reward = $stmt->fetch();

        if (!$reward) {
            throw new RuntimeException('Reward not found.');
        }

        return $reward;
    }

    public function countUsers(): int
    {
        return (int) Database::getConnection()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function countRewards(): int
    {
        return (int) Database::getConnection()->query('SELECT COUNT(*) FROM rewards')->fetchColumn();
    }

    public function countTransactions(): int
    {
        return (int) Database::getConnection()->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
    }

    public function getTotalPointsIssued(): int
    {
        $value = Database::getConnection()->query('SELECT COALESCE(SUM(points), 0) FROM transactions WHERE type = "earn"')->fetchColumn();
        return (int) $value;
    }
}
