<?php

declare(strict_types=1);

require __DIR__ . '/../app/core/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/AuthService.php';
require __DIR__ . '/../app/core/RewardService.php';

$auth = new AuthService();
$auth->requireAdmin();

$service = new RewardService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $pointsRequired = (int) ($_POST['points_required'] ?? 0);

    try {
        $service->addReward($title, $description, $pointsRequired);
        $message = 'Reward added successfully.';
    } catch (Throwable $e) {
        $message = $e->getMessage();
    }
}

$rewards = $service->getRewards();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RewardNet - Rewards</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <button class="nav-toggle" aria-label="Open navigation">☰</button>
    <div class="dashboard-shell">
        <aside class="sidebar">
            <div class="brand">RewardNet</div>
            <nav>
                <a href="/dashboard.php">Dashboard</a>
                <a href="/members.php">Members</a>
                <a href="/ads.php">Ads</a>
                <a href="/transactions.php">Transactions</a>
                <a class="logout-link" href="/logout.php">Logout</a>
            </nav>
        </aside>

        <main class="main-panel">
            <header class="topbar">
                <div>
                    <p class="eyebrow">Catalog</p>
                    <h1>Rewards</h1>
                </div>
            </header>

            <?php if (!empty($message ?? '')): ?>
                <div class="flash"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <section class="panel form-panel">
                <h2>Add reward</h2>
                <form method="post" class="form-grid">
                    <label>
                        Reward title
                        <input type="text" name="title" required>
                    </label>
                    <label>
                        Points required
                        <input type="number" name="points_required" min="1" required>
                    </label>
                    <label class="full-width">
                        Description
                        <textarea name="description" rows="4"></textarea>
                    </label>
                    <button type="submit">Create reward</button>
                </form>
            </section>

            <section class="panel table-panel">
                <div class="panel-header">
                    <h2>Rewards catalog</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Points</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rewards as $reward): ?>
                            <tr>
                                <td><?= (int) $reward['id'] ?></td>
                                <td><?= htmlspecialchars($reward['title']) ?></td>
                                <td><?= (int) $reward['points_required'] ?></td>
                                <td><?= (int) $reward['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
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
