<?php

declare(strict_types=1);

require __DIR__ . '/../app/core/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/AuthService.php';
require __DIR__ . '/../app/core/AdvertisementService.php';

$auth = new AuthService();
$auth->requireLogin();

$adService = new AdvertisementService();
$userId = (int) $_SESSION['rewardnet_user_id'];
$adId = (int) ($_GET['ad_id'] ?? 0);
$message = '';
$ad = null;
$session = null;

if ($adId <= 0) {
    header('Location: /dashboard.php');
    exit;
}

try {
    $ad = $adService->getAdvertisementById($adId);

    if ($ad) {
        // Start or retrieve existing session safely
        try {
            $session = $adService->startAdSession($userId, $adId);
        } catch (Throwable $e) {
            // Handle cases where session exists or fetch manually if needed
            $session = null;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['complete'] ?? '') === '1') {
            $sessionId = (int) ($session['id'] ?? $_POST['session_id'] ?? 0);
            $result = $adService->completeAdSession($userId, $sessionId);
            $message = 'Ad completed. You earned ' . (int) $result['reward_amount'] . ' MB.';
            if (is_array($session)) {
                $session['completion_status'] = 'completed';
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'reward_amount' => (int) $result['reward_amount'],
                ]);
                exit;
            }
        }
    }
} catch (Throwable $e) {
    $message = $e->getMessage();
    $ad = null;
    $session = null;

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
        ]);
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RewardNet - Watch ad</title>
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
                    <p class="eyebrow">Watch to earn</p>
                    <h1>Reward ad</h1>
                </div>
            </header>

            <?php if ($message !== ''): ?>
                <div class="flash"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($ad): ?>
                <section class="panel">
                    <div class="panel-header">
                        <h2><?= htmlspecialchars($ad['title']) ?></h2>
                    </div>
                    <p><?= htmlspecialchars($ad['description']) ?></p>
                    <p><strong>Reward:</strong> <?= (int) $ad['reward_amount'] ?> MB</p>
                    <p><strong>Duration:</strong> <?= (int) $ad['duration_seconds'] ?> seconds</p>

                    <?php if (!empty($ad['media_reference'])): ?>
                        <div style="background:#f3f4f6;padding:20px;border-radius:12px; margin:20px 0;">
                            <video id="reward-video" controls playsinline preload="metadata" style="width:100%; max-height:420px; border-radius:12px; background:#000;">
                                <source src="<?= htmlspecialchars($ad['media_reference']) ?>" type="video/mp4">
                                Your browser does not support HTML5 video.
                            </video>
                        </div>
                    <?php else: ?>
                        <div style="background:#f3f4f6;padding:20px;border-radius:12px; margin:20px 0;">
                            <p>Video placeholder for this ad campaign.</p>
                            <p><em>Complete the ad to receive the reward.</em></p>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($session) || ($session['completion_status'] ?? '') !== 'completed'): ?>
                        <div id="completion-status" class="info-note">Watch the video to the end to claim your reward.</div>
                    <?php else: ?>
                        <div id="completion-status" class="flash">Reward already claimed. You can return to your dashboard.</div>
                    <?php endif; ?>

                    <a class="primary-btn" href="/dashboard.php">Back to dashboard</a>
                </section>
            <?php else: ?>
                <section class="panel">
                    <p>This ad is unavailable or already processed.</p>
                    <a class="primary-btn" href="/dashboard.php">Return to dashboard</a>
                </section>
            <?php endif; ?>
        </main>
    </div>
    <script src="/assets/js/nav.js"></script>
    <script>
        const rewardVideo = document.getElementById('reward-video');
        const completionStatus = document.getElementById('completion-status');
        const adId = <?= (int) ($ad['id'] ?? 0) ?>;
        const sessionId = <?= (int) ($session['id'] ?? 0) ?>;
        const completionUrl = window.location.href;
        let rewardGranted = false;

        if (rewardVideo && adId > 0 && completionStatus) {
            rewardVideo.addEventListener('ended', function () {
                if (rewardGranted) return;
                rewardGranted = true;

                const formData = new FormData();
                formData.append('complete', '1');
                formData.append('ad_id', String(adId));
                formData.append('session_id', String(sessionId));

                fetch(completionUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(async (response) => {
                    const data = await response.json().catch(() => null);
                    if (response.ok && data && data.success) {
                        completionStatus.className = 'flash';
                        completionStatus.textContent = data.message || 'Ad completed. Reward awarded.';
                        return;
                    }

                    completionStatus.className = 'flash';
                    completionStatus.textContent = (data && data.message) || 'This ad could not be completed.';
                })
                .catch(() => {
                    completionStatus.className = 'flash';
                    completionStatus.textContent = 'Reward could not be processed. Please try again.';
                });
            });
        }
    </script>
</body>
</html>