<?php

declare(strict_types=1);

require __DIR__ . '/../app/core/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/AuthService.php';
require __DIR__ . '/../app/core/RewardService.php';

$auth = new AuthService();
$auth->requireAdmin();

$service = new RewardService();
$members = $service->getUsers();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RewardNet - Members</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <button class="nav-toggle" aria-label="Open navigation">☰</button>
    <div class="dashboard-shell">
        <aside class="sidebar">
            <div class="brand">RewardNet</div>
            <nav>
                <a href="/dashboard.php">Dashboard</a>
                <a class="active" href="/members.php">Members</a>
                <a href="/ads.php">Ads</a>
                <a href="/transactions.php">Transactions</a>
                <a class="logout-link" href="/logout.php">Logout</a>
            </nav>
        </aside>

        <main class="main-panel">
            <header class="topbar">
                <div>
                    <p class="eyebrow">Community</p>
                    <h1>Members</h1>
                </div>
            </header>

            <section class="panel table-panel">
                <div class="panel-header">
                    <h2>Member list</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?= (int) $member['id'] ?></td>
                                <td><?= htmlspecialchars($member['full_name']) ?></td>
                                <td><?= htmlspecialchars($member['email']) ?></td>
                                <td><?= htmlspecialchars($member['role']) ?></td>
                                <td><?= (int) $member['points'] ?></td>
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
