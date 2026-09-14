<?php

declare(strict_types=1);

require __DIR__ . '/../app/core/bootstrap.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/AuthService.php';
require __DIR__ . '/../app/core/AdvertisementService.php';

$auth = new AuthService();
$auth->requireAdmin();

$service = new AdvertisementService();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        try {
            $mediaReference = null;

            if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/ads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'application/octet-stream'];
                $ext = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
                $allowedExts = ['mp4', 'webm', 'ogg', 'mov'];
                if (!in_array($ext, $allowedExts, true)) {
                    throw new InvalidArgumentException('Only MP4, WebM, OGG, and MOV video files are allowed.');
                }

                if (function_exists('mime_content_type')) {
                    $mime = mime_content_type($_FILES['media_file']['tmp_name']) ?: '';
                    if (!empty($mime) && !in_array($mime, $allowedTypes, true) && strpos($mime, 'video/') !== 0) {
                        throw new InvalidArgumentException('Invalid video file format.');
                    }
                }

                $filename = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['media_file']['name']));
                $target = $uploadDir . '/' . $filename;
                if (!move_uploaded_file($_FILES['media_file']['tmp_name'], $target)) {
                    throw new RuntimeException('Unable to upload the ad video.');
                }

                $mediaReference = '/uploads/ads/' . $filename;
            }

            $service->createAdvertisement(
                (string) ($_POST['title'] ?? ''),
                (string) ($_POST['description'] ?? ''),
                (int) ($_POST['duration_seconds'] ?? 0),
                (int) ($_POST['reward_amount'] ?? 0),
                (string) ($_POST['status'] ?? 'active'),
                $mediaReference
            );
            $message = 'Advertisement created successfully.';
        } catch (Throwable $e) {
            $message = $e->getMessage();
        }
    }

    if ($action === 'delete') {
        $adId = (int) ($_POST['ad_id'] ?? 0);
        if ($adId > 0) {
            $service->deleteAdvertisement($adId);
            $message = 'Advertisement removed.';
        }
    }
}

$ads = $service->getAdvertisements(true);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RewardNet - Ads</title>
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
                <a class="active" href="/ads.php">Ads</a>
                <a href="/transactions.php">Transactions</a>
                <a class="logout-link" href="/logout.php">Logout</a>
            </nav>
        </aside>

        <main class="main-panel">
            <header class="topbar">
                <div>
                    <p class="eyebrow">Advertiser portal</p>
                    <h1>Ad management</h1>
                </div>
            </header>

            <?php if ($message !== ''): ?>
                <div class="flash"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <section class="panel form-panel">
                <h2>Create advertisement</h2>
                <form method="post" class="form-grid" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <label>
                        Ad title
                        <input type="text" name="title" required>
                    </label>
                    <label>
                        Duration (seconds)
                        <input type="number" name="duration_seconds" min="1" max="60" required>
                    </label>
                    <label>
                        Reward (MB)
                        <input type="number" name="reward_amount" min="1" required>
                    </label>
                    <label class="full-width">
                        Description
                        <textarea name="description" rows="4" required></textarea>
                    </label>
                    <div class="full-width info-note">Ads are created as active campaigns and run for up to 60 seconds.</div>
                    <div class="full-width upload-field">
                        <span class="upload-label">Video file</span>
                        <div id="drop_zone" class="upload-dropzone" tabindex="0" role="button" aria-label="Upload video file">
                            <input id="media_file" type="file" name="media_file" accept="video/*">
                            <div class="upload-dropzone-inner">
                                <div class="upload-icon-wrap">
                                    <span class="upload-plus">+</span>
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h7.2a2.5 2.5 0 0 1 2.5 2.5v1.2l2.3-1.2a1.5 1.5 0 0 1 2.2 1.3v7.2a1.5 1.5 0 0 1-2.2 1.3l-2.3-1.2v1.2A2.5 2.5 0 0 1 13.7 19H6.5A2.5 2.5 0 0 1 4 16.5v-9Zm8.5 1.7h2.5v2.3h2.3v2.1h-2.3v2.3h-2.5v-2.3H10v-2.1h2.5v-2.3Z"/>
                                    </svg>
                                </div>
                                <div class="upload-content">
                                    <strong>Drop your video here</strong>
                                    <span>or browse from your device</span>
                                </div>
                            </div>
                            <div class="upload-preview hidden" id="video_preview_wrap">
                                <video id="video_preview" controls playsinline muted></video>
                            </div>
                            <div id="media_file_name" class="upload-file-name">No file selected</div>
                        </div>
                    </div>
                    <button type="submit">Save ad</button>
                </form>
            </section>

            <section class="panel table-panel">
                <div class="panel-header">
                    <h2>Ad catalog</h2>
                </div>
                <?php if (empty($ads)): ?>
                    <p>No advertisements created yet.</p>
                <?php else: ?>
                    <div class="ad-catalog-grid">
                        <?php foreach ($ads as $ad): ?>
                            <article class="ad-catalog-card">
                                <div class="ad-catalog-thumb" aria-label="Video for <?= htmlspecialchars($ad['title']) ?>">
                                    <span>Video</span>
                                </div>
                                <div class="ad-catalog-body">
                                    <div class="ad-catalog-head">
                                        <h3><?= htmlspecialchars($ad['title']) ?></h3>
                                        <span class="badge <?= htmlspecialchars($ad['status']) === 'active' ? 'success' : 'neutral' ?>"><?= htmlspecialchars($ad['status']) ?></span>
                                    </div>
                                    <p><?= htmlspecialchars($ad['description']) ?></p>
                                    <div class="ad-catalog-meta">
                                        <span><?= (int) $ad['reward_amount'] ?> MB</span>
                                        <span><?= (int) $ad['duration_seconds'] ?>s</span>
                                    </div>
                                    <form method="post" class="ad-delete-form">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="ad_id" value="<?= (int) $ad['id'] ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script src="/assets/js/nav.js"></script>
    <script>
        const mediaInput = document.getElementById('media_file');
        const mediaFileName = document.getElementById('media_file_name');
        const dropZone = document.getElementById('drop_zone');
        const previewWrap = document.getElementById('video_preview_wrap');
        const preview = document.getElementById('video_preview');

        function updateSelectedFile(file) {
            if (!mediaInput || !mediaFileName) return;

            mediaFileName.textContent = file ? file.name : 'No file selected';

            if (file && preview && previewWrap) {
                const objectUrl = URL.createObjectURL(file);
                preview.src = objectUrl;
                previewWrap.classList.remove('hidden');
            } else if (previewWrap && preview) {
                preview.removeAttribute('src');
                previewWrap.classList.add('hidden');
            }
        }

        if (mediaInput) {
            mediaInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                updateSelectedFile(file);
            });
        }

        if (dropZone && mediaInput) {
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropZone.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropZone.classList.remove('dragover');
                });
            });

            dropZone.addEventListener('drop', function (event) {
                const file = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files[0] : null;
                if (file) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    mediaInput.files = dataTransfer.files;
                    updateSelectedFile(file);
                }
            });

            dropZone.addEventListener('click', function (event) {
                if (event.target !== mediaInput) {
                    mediaInput.click();
                }
            });

            dropZone.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    mediaInput.click();
                }
            });
        }
    </script>
</body>
</html>
