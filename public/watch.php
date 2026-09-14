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
$userRole = (string) ($_SESSION['rewardnet_user_role'] ?? 'member');
$isAdmin = ($userRole === 'admin');

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

$isCompleted = !empty($session) && ($session['completion_status'] ?? '') === 'completed';
$durationSeconds = $ad ? max(1, (int) $ad['duration_seconds']) : 10;
$hasVideo = $ad && !empty($ad['media_reference']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RewardNet - Watch ad</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .ad-player-box {
            background: var(--panel-soft);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
        }
        .simulated-ad-screen {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(139, 92, 246, 0.15));
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 28px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .sim-badge {
            display: inline-block;
            background: rgba(37, 99, 235, 0.15);
            color: var(--primary);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 999px;
            margin-bottom: 12px;
        }
        .simulated-ad-screen h3 {
            margin: 0 0 8px;
            font-size: 1.3rem;
            color: var(--text);
        }
        .simulated-ad-screen p {
            margin: 0 0 16px;
            color: var(--muted);
            font-size: 0.95rem;
        }
        .sim-brand-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background: var(--panel);
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .sim-reward {
            color: var(--success);
        }
        .ad-timer-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
        }
        .ad-progress-wrap {
            flex: 1;
            height: 10px;
            background: var(--line);
            border-radius: 999px;
            overflow: hidden;
        }
        .ad-progress-bar {
            height: 100%;
            width: 0%;
            background: var(--primary);
            border-radius: 999px;
            transition: width 0.25s linear;
        }
        .ad-timer-badge {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text);
            min-width: 60px;
            text-align: right;
            font-family: monospace;
        }
    </style>
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

                    <div class="ad-player-box">
                        <?php if ($hasVideo): ?>
                            <video id="reward-video" controls playsinline preload="metadata" style="width:100%; max-height:420px; border-radius:12px; background:#000;">
                                <source src="<?= htmlspecialchars($ad['media_reference']) ?>">
                                Your browser does not support HTML5 video.
                            </video>
                        <?php else: ?>
                            <div class="simulated-ad-screen">
                                <span class="sim-badge">Sponsored Campaign</span>
                                <h3><?= htmlspecialchars($ad['title']) ?></h3>
                                <p><?= htmlspecialchars($ad['description']) ?></p>
                                <div class="sim-brand-banner">
                                    <span>✦ RewardNet Sponsor</span>
                                    <span class="sim-reward">+<?= (int) $ad['reward_amount'] ?> MB Data</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="ad-timer-row">
                            <div class="ad-progress-wrap">
                                <div id="ad-progress-bar" class="ad-progress-bar"></div>
                            </div>
                            <span id="ad-timer-text" class="ad-timer-badge"><?= $isCompleted ? 'Done' : $durationSeconds . 's' ?></span>
                        </div>

                        <?php if (!$isCompleted && !$hasVideo): ?>
                            <button id="start-sim-ad-btn" class="primary-btn" style="margin-top: 14px; width: 100%;">▶ Start Watching Ad</button>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isCompleted): ?>
                        <div id="completion-status" class="info-note"><?= $hasVideo ? 'Watch the video to the end to claim your reward.' : 'Click "Start Watching Ad" and wait for the countdown to claim your reward.' ?></div>
                    <?php else: ?>
                        <div id="completion-status" class="flash">Reward already claimed. You can return to your dashboard.</div>
                    <?php endif; ?>

                    <a class="secondary-btn" href="/dashboard.php" style="margin-top: 16px;">Back to dashboard</a>
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
        const startSimBtn = document.getElementById('start-sim-ad-btn');
        const progressBar = document.getElementById('ad-progress-bar');
        const timerText = document.getElementById('ad-timer-text');
        const completionStatus = document.getElementById('completion-status');

        const adId = <?= (int) ($ad['id'] ?? 0) ?>;
        const sessionId = <?= (int) ($session['id'] ?? 0) ?>;
        const totalDuration = <?= (int) $durationSeconds ?>;
        const isAlreadyCompleted = <?= $isCompleted ? 'true' : 'false' ?>;
        let rewardGranted = isAlreadyCompleted;

        function claimReward() {
            if (rewardGranted) return;
            rewardGranted = true;

            const formData = new FormData();
            formData.append('complete', '1');
            formData.append('ad_id', String(adId));
            formData.append('session_id', String(sessionId));

            if (completionStatus) {
                completionStatus.className = 'info-note';
                completionStatus.textContent = 'Verifying completion and claiming reward...';
            }

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(async (response) => {
                const data = await response.json().catch(() => null);
                if (response.ok && data && data.success) {
                    if (completionStatus) {
                        completionStatus.className = 'flash';
                        completionStatus.style.background = '#dcfce7';
                        completionStatus.style.color = '#166534';
                        completionStatus.style.borderColor = '#86efac';
                        completionStatus.textContent = data.message || 'Ad completed! Reward awarded.';
                    }
                    if (progressBar) progressBar.style.width = '100%';
                    if (timerText) timerText.textContent = 'Done!';
                    if (startSimBtn) startSimBtn.remove();
                    return;
                }

                if (completionStatus) {
                    completionStatus.className = 'flash';
                    completionStatus.textContent = (data && data.message) || 'This ad could not be completed.';
                }
            })
            .catch(() => {
                if (completionStatus) {
                    completionStatus.className = 'flash';
                    completionStatus.textContent = 'Reward could not be processed. Please try again.';
                }
            });
        }

        // Mode 1: Video ad
        if (rewardVideo && !isAlreadyCompleted) {
            rewardVideo.addEventListener('timeupdate', function () {
                if (rewardVideo.duration > 0) {
                    const percent = (rewardVideo.currentTime / rewardVideo.duration) * 100;
                    if (progressBar) progressBar.style.width = Math.min(100, percent) + '%';
                    const remaining = Math.max(0, Math.ceil(rewardVideo.duration - rewardVideo.currentTime));
                    if (timerText) timerText.textContent = remaining + 's';
                }
            });

            rewardVideo.addEventListener('ended', function () {
                if (progressBar) progressBar.style.width = '100%';
                if (timerText) timerText.textContent = '0s';
                claimReward();
            });
        }

        // Mode 2: Simulated ad (when no video file exists)
        if (startSimBtn && !isAlreadyCompleted) {
            startSimBtn.addEventListener('click', function () {
                startSimBtn.disabled = true;
                startSimBtn.textContent = 'Watching ad...';
                startSimBtn.style.opacity = '0.7';

                let elapsed = 0;
                if (progressBar) progressBar.style.width = '0%';

                const interval = setInterval(() => {
                    elapsed += 0.25;
                    const percent = (elapsed / totalDuration) * 100;
                    if (progressBar) progressBar.style.width = Math.min(100, percent) + '%';
                    
                    const remaining = Math.max(0, Math.ceil(totalDuration - elapsed));
                    if (timerText) timerText.textContent = remaining + 's';

                    if (elapsed >= totalDuration) {
                        clearInterval(interval);
                        if (progressBar) progressBar.style.width = '100%';
                        if (timerText) timerText.textContent = '0s';
                        claimReward();
                    }
                }, 250);
            });
        }

        if (isAlreadyCompleted) {
            if (progressBar) progressBar.style.width = '100%';
            if (timerText) timerText.textContent = 'Done';
        }
    </script>
</body>
</html>