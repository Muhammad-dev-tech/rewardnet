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
$adsService = new AdvertisementService();
$user = $service->getUserById((int) $_SESSION['rewardnet_user_id']);
$transactions = $service->getTransactions(5);
$ads = $adsService->getAdvertisements();
$isAdmin = $user['role'] === 'admin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RewardNet - Dashboard</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <button class="nav-toggle" aria-label="Open navigation">☰</button>
    <div class="dashboard-shell">
        <aside class="sidebar">
            <div class="brand">RewardNet</div>
            <nav>
                <a class="active" href="/dashboard.php">Dashboard</a>
                <?php if ($isAdmin): ?>
                    <a href="/members.php">Members</a>
                    <a href="/ads.php">Ads</a>
                    <a href="/transactions.php">Transactions</a>
                <?php else: ?>
                    <a href="/transactions.php">My activity</a>
                <?php endif; ?>
                <a class="logout-link" href="/logout.php">Logout</a>
            </nav>
        </aside>

        <main class="main-panel">
            <header class="topbar">
                <div>
                    <p class="eyebrow">Dashboard</p>
                    <h1>Welcome, <?= htmlspecialchars($user['full_name']) ?></h1>
                </div>
                <span class="status-pill"><?= htmlspecialchars($user['role'] === 'admin' ? 'Admin' : 'Member') ?></span>
            </header>

            <?php if ($isAdmin): ?>
                <section class="panel">
                    <div class="panel-header">
                        <h2>Summary</h2>
                    </div>
                    <div class="info-note">A quick snapshot of your account activity and reward performance.</div>
                </section>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <section class="panel">
                    <div class="panel-header">
                        <h2>Performance</h2>
                    </div>
                    <div class="stats-grid">
                        <a class="stat-card-link" href="/members.php">
                            <div class="stat-card orange">
                                <span>Members</span>
                                <strong><?= $service->countUsers() ?></strong>
                            </div>
                        </a>
                        <a class="stat-card-link" href="/ads.php">
                            <div class="stat-card purple">
                                <span>Active ads</span>
                                <strong><?= count($adsService->getAdvertisements()) ?></strong>
                            </div>
                        </a>
                        <div class="stat-card green">
                            <span>Reward catalog</span>
                            <strong><?= $service->countRewards() ?></strong>
                        </div>
                        <div class="stat-card blue">
                            <span>Points issued</span>
                            <strong><?= $service->getTotalPointsIssued() ?> MB</strong>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <h2>Active ad campaigns</h2>
                    </div>
                    <?php $activeAds = $adsService->getAdvertisements(); ?>
                    <?php if (empty($activeAds)): ?>
                        <p>No active ads right now.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Reward</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($activeAds, 0, 5) as $ad): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ad['title']) ?></td>
                                        <td><?= (int) $ad['reward_amount'] ?> MB</td>
                                        <td><?= (int) $ad['duration_seconds'] ?>s</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>
            <?php else: ?>
                <section class="stats-grid">
                    <div class="stat-card purple">
                        <span>Data balance</span>
                        <strong><?= (int) $user['points'] ?> MB</strong>
                    </div>
                    <div class="stat-card green">
                        <span>Role</span>
                        <strong><?= htmlspecialchars($user['role']) ?></strong>
                    </div>
                    <div class="stat-card blue">
                        <span>Transactions</span>
                        <strong><?= $service->countTransactions() ?></strong>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <h2>Available ads</h2>
                    </div>
                    <?php if (empty($ads)): ?>
                        <p>No active ads available right now.</p>
                    <?php else: ?>
                        <div class="ad-gallery">
                            <?php foreach ($ads as $ad): ?>
                                <article class="ad-card">
                                    <div class="ad-thumb" aria-label="Ad thumbnail for <?= htmlspecialchars($ad['title']) ?>">
                                        <span>Video</span>
                                    </div>
                                    <div class="ad-card-body">
                                        <div class="ad-card-header">
                                            <h3><?= htmlspecialchars($ad['title']) ?></h3>
                                            <span class="reward-pill"><?= (int) $ad['reward_amount'] ?> MB</span>
                                        </div>
                                        <p><?= htmlspecialchars($ad['description']) ?></p>
                                        <div class="ad-meta">
                                            <span><?= (int) $ad['duration_seconds'] ?>s</span>
                                            <span>Active</span>
                                        </div>
                                        <div class="ad-duration-row">
                                            <span class="ad-duration-label">Duration</span>
                                            <span class="ad-duration-value"><?= (int) $ad['duration_seconds'] ?> sec</span>
                                        </div>
                                        <a class="primary-btn ad-action" href="/watch.php?ad_id=<?= (int) $ad['id'] ?>">Watch ad</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header">
                    <h2><?= $isAdmin ? 'Recent activity' : 'Recent activity' ?></h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Type</th>
                            <th>Points</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td><?= htmlspecialchars($txn['user_name']) ?></td>
                                <td><?= htmlspecialchars($txn['type']) ?></td>
                                <td><?= (int) $txn['points'] ?></td>
                                <td><?= htmlspecialchars($txn['description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    <script src="/assets/js/nav.js"></script>
</body>
</html>
