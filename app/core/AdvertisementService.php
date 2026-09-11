<?php

declare(strict_types=1);

final class AdvertisementService
{
    public function createAdvertisement(string $title, string $description, int $durationSeconds, int $rewardAmount, string $status = 'active', ?string $mediaReference = null): int
    {
        $title = trim($title);
        $description = trim($description);
        $status = strtolower(trim($status));
        $mediaReference = $mediaReference !== null ? trim($mediaReference) : null;

        if ($title === '' || $durationSeconds <= 0 || $rewardAmount <= 0) {
            throw new InvalidArgumentException('Advertisement title, duration, and reward are required.');
        }

        if ($durationSeconds > 60) {
            throw new InvalidArgumentException('Advertisement duration cannot exceed 60 seconds.');
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO advertisements (title, description, duration_seconds, reward_amount, status, media_reference) VALUES (?, ?, ?, ?, ?, ?)' 
        );
        $stmt->execute([$title, $description, $durationSeconds, $rewardAmount, $status, $mediaReference]);

        return (int) $pdo->lastInsertId();
    }

    public function getAdvertisements(bool $includeInactive = false): array
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT * FROM advertisements';
        if (!$includeInactive) {
            $sql .= ' WHERE status = "active"';
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getAdvertisementById(int $adId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM advertisements WHERE id = ? LIMIT 1');
        $stmt->execute([$adId]);
        $ad = $stmt->fetch();

        if (!$ad) {
            throw new RuntimeException('Advertisement not found.');
        }

        return $ad;
    }

    public function startAdSession(int $userId, int $adId): array
    {
        $ad = $this->getAdvertisementById($adId);
        if ($ad['status'] !== 'active') {
            throw new RuntimeException('This advertisement is not active.');
        }

        $pdo = Database::getConnection();
        $existing = $pdo->prepare(
            'SELECT * FROM ad_views WHERE user_id = ? AND advertisement_id = ? ORDER BY started_at DESC LIMIT 1'
        );
        $existing->execute([$userId, $adId]);
        $currentSession = $existing->fetch();

        if ($currentSession && $currentSession['completion_status'] === 'completed') {
            throw new RuntimeException('This user has already earned the reward for this ad.');
        }

        if ($currentSession && $currentSession['completion_status'] === 'incomplete') {
            return [
                'id' => (int) $currentSession['id'],
                'user_id' => $userId,
                'advertisement_id' => $adId,
                'completion_status' => 'incomplete',
                'reward_amount' => 0,
            ];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO ad_views (user_id, advertisement_id, completion_status, reward_amount) VALUES (?, ?, "incomplete", 0)'
        );
        $stmt->execute([$userId, $adId]);

        return [
            'id' => (int) $pdo->lastInsertId(),
            'user_id' => $userId,
            'advertisement_id' => $adId,
            'completion_status' => 'incomplete',
            'reward_amount' => 0,
        ];
    }

    public function completeAdSession(int $userId, int $adViewId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT av.*, a.reward_amount, a.status, a.duration_seconds
             FROM ad_views av
             INNER JOIN advertisements a ON a.id = av.advertisement_id
             WHERE av.id = ? AND av.user_id = ?'
        );
        $stmt->execute([$adViewId, $userId]);
        $adView = $stmt->fetch();

        if (!$adView) {
            throw new RuntimeException('Invalid ad session.');
        }

        if ($adView['status'] !== 'active') {
            throw new RuntimeException('This advertisement is not active.');
        }

        if ($adView['completion_status'] === 'completed') {
            throw new RuntimeException('This ad has already been completed.');
        }

        $duplicateCheck = $pdo->prepare(
            'SELECT id FROM ad_views WHERE user_id = ? AND advertisement_id = ? AND completion_status = "completed" AND id != ? LIMIT 1'
        );
        $duplicateCheck->execute([$userId, $adView['advertisement_id'], $adViewId]);
        if ($duplicateCheck->fetch()) {
            throw new RuntimeException('A reward has already been issued for this advertisement.');
        }

        $rewardAmount = (int) $adView['reward_amount'];
        $rewardAmount = (int) $adView['reward_amount'];

        $pdo->beginTransaction();
        try {
            $update = $pdo->prepare(
                'UPDATE ad_views SET completion_status = "completed", completed_at = NOW(), reward_amount = ? WHERE id = ?'
            );
            $update->execute([$rewardAmount, $adViewId]);

            $transactionStmt = $pdo->prepare(
                'INSERT INTO reward_transactions (user_id, ad_view_id, amount, transaction_type, description) VALUES (?, ?, ?, "credit", "Reward earned from advertisement completion")'
            );
            $transactionStmt->execute([$userId, $adViewId, $rewardAmount]);

            $balanceStmt = $pdo->prepare('UPDATE users SET points = points + ? WHERE id = ?');
            $balanceStmt->execute([$rewardAmount, $userId]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return [
            'ad_view_id' => $adViewId,
            'reward_amount' => $rewardAmount,
            'completed' => true,
        ];
    }

    public function deleteAdvertisement(int $adId): void
    {
        $pdo = Database::getConnection();
        $ad = $this->getAdvertisementById($adId);

        if (!empty($ad['media_reference'])) {
            $filePath = __DIR__ . '/../../public' . $ad['media_reference'];
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }

        $stmt = $pdo->prepare('DELETE FROM advertisements WHERE id = ?');
        $stmt->execute([$adId]);
    }

    public function getAdViewsByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT av.*, a.title, a.description, a.reward_amount AS ad_reward, a.duration_seconds
             FROM ad_views av
             INNER JOIN advertisements a ON a.id = av.advertisement_id
             WHERE av.user_id = ?
             ORDER BY av.completed_at DESC, av.started_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
