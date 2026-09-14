<?php

declare(strict_types=1);

$config = require __DIR__ . '/../app/config/config.php';

$host = $config['db']['host'];
$port = !empty($config['db']['port']) ? (int) $config['db']['port'] : 3306;
$dbName = $config['db']['database'];
$user = $config['db']['username'];
$pass = $config['db']['password'];
$charset = $config['db']['charset'];

$messages = [];
$errors = [];
$tables = [];

try {
    // Step 1: Connect to MySQL server (without specific db first, to create if missing)
    try {
        $serverPdo = new PDO(
            "mysql:host={$host};port={$port};charset={$charset}",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $messages[] = "Database '{$dbName}' verified or created.";
    } catch (Throwable $e) {
        // In cloud environments like Railway, CREATE DATABASE may not be permitted if DB already exists
        $messages[] = "Notice: Connected without CREATE DATABASE privilege (" . $e->getMessage() . "). Proceeding...";
    }

    // Step 2: Connect directly to the target database
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $messages[] = "Connected to database '{$dbName}' successfully.";

    // Step 3: Read and execute schema.sql
    $schemaPath = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schemaPath)) {
        throw new RuntimeException("Schema file not found at {$schemaPath}");
    }

    $sql = file_get_contents($schemaPath);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException("Schema file is empty.");
    }

    $pdo->exec($sql);
    $messages[] = "Executed database schema successfully. All tables and indexes are ready.";

    // Step 4: Verify created tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $messages[] = "Active tables in '{$dbName}': " . implode(', ', $tables);

} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

$isCli = (php_sapi_name() === 'cli');

if ($isCli) {
    if (!empty($errors)) {
        echo "Database setup failed:\n" . implode("\n", $errors) . "\n";
        exit(1);
    }
    echo "Database setup succeeded:\n" . implode("\n", $messages) . "\n";
    exit(0);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RewardNet - Database Setup</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="landing-shell">
        <div class="landing-card" style="max-width: 640px;">
            <p class="eyebrow">System Utility</p>
            <h1>Database Setup</h1>

            <?php if (!empty($errors)): ?>
                <div class="flash" style="background:#fee2e2; color:#991b1b; border-color:#f87171; margin: 18px 0;">
                    <strong>Setup failed:</strong>
                    <ul style="margin: 8px 0 0 16px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div style="text-align: left; background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; margin-bottom: 18px;">
                    <p style="margin-top:0;"><strong>Connection Diagnostic Info:</strong></p>
                    <ul style="margin: 0 0 0 16px; padding: 0;">
                        <li><strong>Host:</strong> <code><?= htmlspecialchars((string) $host) ?></code></li>
                        <li><strong>Port:</strong> <code><?= htmlspecialchars((string) $port) ?></code></li>
                        <li><strong>Database:</strong> <code><?= htmlspecialchars((string) $dbName) ?></code></li>
                        <li><strong>User:</strong> <code><?= htmlspecialchars((string) $user) ?></code></li>
                        <li><strong>Has MYSQL_URL:</strong> <?= !empty(getenv('MYSQL_URL') ?: ($_ENV['MYSQL_URL'] ?? ($_SERVER['MYSQL_URL'] ?? ''))) ? 'Yes' : 'No' ?></li>
                        <li><strong>Available Env Keys:</strong> <code><?= htmlspecialchars(implode(', ', array_keys(array_filter(array_merge($_ENV, $_SERVER, getenv()), fn($k) => stripos($k, 'mysql') !== false || stripos($k, 'db') !== false, ARRAY_FILTER_USE_KEY)))) ?></code></li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="flash" style="background:#dcfce7; color:#166534; border-color:#86efac; margin: 18px 0;">
                    <strong>Setup completed successfully!</strong>
                    <ul style="margin: 8px 0 0 16px;">
                        <?php foreach ($messages as $msg): ?>
                            <li><?= htmlspecialchars($msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php if (!empty($tables)): ?>
                    <div style="margin: 16px 0; text-align: left;">
                        <p><strong>Configured tables:</strong></p>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                            <?php foreach ($tables as $tbl): ?>
                                <span class="status-pill"><?= htmlspecialchars($tbl) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="cta-row" style="margin-top: 24px;">
                <a class="primary-btn" href="/dashboard.php">Go to Dashboard</a>
                <a class="secondary-btn" href="/login.php">Login</a>
            </div>
        </div>
    </div>
</body>
</html>
