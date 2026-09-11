<?php

declare(strict_types=1);

require __DIR__ . '/../app/core/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/AuthService.php';
require __DIR__ . '/../app/core/RewardService.php';
require __DIR__ . '/../app/core/AdvertisementService.php';

$auth = new AuthService();
$auth->requireLogin();

$service = new RewardService();
$adService = new AdvertisementService();

$isAdmin = ($_SESSION['rewardnet_user_role'] ?? '') === 'admin';
$message = '';

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $points = (int) ($_POST['points'] ?? 0);
    $description = $_POST['description'] ?? 'Manual points adjustment';

    try {
        $service->awardPoints($userId, $points, $description);
        $message = 'Points added successfully.';
    } catch (Throwable $e) {
        $message = $e->getMessage();
    }
}

$transactions = $service->getTransactions(12);
$members = $service->getUsers();
$userHistory = $isAdmin ? [] : $adService->getAdViewsByUser((int) $_SESSION['rewardnet_user_id']);

function formatActivityTimestamp(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '—';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
    if ($date === false) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    return htmlspecialchars($date->format('M j, Y g:i:s A'), ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RewardNet - Transactions</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <button class="nav-toggle" aria-label="Open navigation">☰</button>
    <div class="dashboard-shell">
        <aside class="sidebar">
            <div class="brand">RewardNet</div>
            <nav>
                <a href="/dashboard.php">Dashboard</a>
                <?php if ($isAdmin): ?>
                    <a href="/members.php">Members</a>
                    <a href="/ads.php">Ads</a>
                    <a class="active" href="/transactions.php">Transactions</a>
                <?php else: ?>
                    <a class="active" href="/transactions.php">My activity</a>
                <?php endif; ?>
                <a class="logout-link" href="/logout.php">Logout</a>
            </nav>
        </aside>

        <main class="main-panel">
            <header class="topbar">
                <div>
                    <p class="eyebrow">Activity</p>
                    <h1><?= $isAdmin ? 'Transactions' : 'My activity' ?></h1>
                </div>
            </header>

            <?php if (!empty($message)): ?>
                <div class="flash"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <section class="panel table-panel">
                    <div class="panel-header">
                        <h2>Latest transactions</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Points</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= (int) $transaction['id'] ?></td>
                                    <td><?= htmlspecialchars($transaction['user_name']) ?></td>
                                    <td><?= htmlspecialchars($transaction['type']) ?></td>
                                    <td><?= (int) $transaction['points'] ?></td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php else: ?>
                <section class="panel table-panel">
                    <div class="panel-header">
                        <h2>Ad watch history</h2>
                    </div>
                    <?php if (empty($userHistory)): ?>
                        <p>You have not watched any ads yet. Complete an ad to start earning rewards.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Ad</th>
                                    <th>Duration</th>
                                    <th>Reward</th>
                                    <th>Status</th>
                                    <th>Watched</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userHistory as $entry): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($entry['title']) ?></td>
                                        <td><?= (int) ($entry['duration_seconds'] ?? 0) ?> sec</td>
                                        <td><?= (int) ($entry['ad_reward'] ?? 0) ?> MB</td>
                                        <td><?= htmlspecialchars($entry['completion_status']) ?></td>
                                        <td><?= formatActivityTimestamp((string) ($entry['completed_at'] ?? $entry['started_at'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
    <script src="/assets/js/nav.js"></script>
</body>
</html>
