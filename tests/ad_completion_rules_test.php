<?php

declare(strict_types=1);

require __DIR__ . '/../app/core/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/AuthService.php';
require __DIR__ . '/../app/core/AdvertisementService.php';

$email = 'duplicate_reward_' . time() . '@example.com';
$auth = new AuthService();
$service = new AdvertisementService();

$userId = $auth->register('Duplicate Reward User', $email, 'password123');
$adId = $service->createAdvertisement('Double Reward Guard', 'Should award once only', 5, 50);

$firstSession = $service->startAdSession($userId, $adId);
$firstResult = $service->completeAdSession($userId, (int) $firstSession['id']);

try {
    $secondSession = $service->startAdSession($userId, $adId);
    $service->completeAdSession($userId, (int) $secondSession['id']);
    echo "DUPLICATE_REWARD_TEST_FAILED\n";
    exit(1);
} catch (Throwable $e) {
    $pdo = Database::getConnection();
    $count = (int) $pdo->query(
        'SELECT COUNT(*) FROM reward_transactions WHERE user_id = ' . (int) $userId . ' AND ad_view_id IN (SELECT id FROM ad_views WHERE advertisement_id = ' . (int) $adId . ')'
    )->fetchColumn();

    if ($count !== 1) {
        echo "DUPLICATE_REWARD_TEST_FAILED_COUNT:$count\n";
        exit(1);
    }

    echo "DUPLICATE_REWARD_TEST_OK:$count\n";
}
