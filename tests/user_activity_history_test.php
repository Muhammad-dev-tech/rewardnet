<?php

declare(strict_types=1);

require __DIR__ . '/../app/core/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/AuthService.php';
require __DIR__ . '/../app/core/AdvertisementService.php';

$email = 'activity_history_test_' . time() . '@example.com';
$auth = new AuthService();
$service = new AdvertisementService();

$userId = $auth->register('Activity User', $email, 'password123');
$adId = $service->createAdvertisement('History Ad', 'Earn points by completing this ad.', 18, 42);
$session = $service->startAdSession($userId, $adId);
$service->completeAdSession($userId, (int) $session['id']);

$history = $service->getAdViewsByUser($userId);
if (empty($history)) {
    echo "USER_ACTIVITY_HISTORY_EMPTY\n";
    exit(1);
}

$first = $history[0];
if ((int) $first['duration_seconds'] !== 18) {
    echo 'USER_ACTIVITY_MISSING_DURATION:' . ($first['duration_seconds'] ?? 'missing') . "\n";
    exit(1);
}

if ((string) $first['completion_status'] !== 'completed') {
    echo 'USER_ACTIVITY_NOT_COMPLETED\n';
    exit(1);
}

echo "USER_ACTIVITY_OK\n";
