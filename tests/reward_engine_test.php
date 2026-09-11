<?php

declare(strict_types=1);

require __DIR__ . '/../app/core/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/AuthService.php';
require __DIR__ . '/../app/core/RewardService.php';
require __DIR__ . '/../app/core/AdvertisementService.php';

$email = 'reward_engine_test_' . time() . '@example.com';
$auth = new AuthService();
$service = new AdvertisementService();

$userId = $auth->register('Reward Engine Tester', $email, 'password123');
$adId = $service->createAdvertisement('Welcome Reward', 'Sample reward ad', 10, 100);
$adView = $service->startAdSession($userId, $adId);
$result = $service->completeAdSession($userId, (int) $adView['id']);

if ($result['reward_amount'] <= 0) {
    echo "REWARD_ENGINE_FAILED\n";
    exit(1);
}

echo "REWARD_ENGINE_OK:" . $result['reward_amount'] . "\n";
