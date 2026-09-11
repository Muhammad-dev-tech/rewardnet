<?php

declare(strict_types=1);

$schemaPath = __DIR__ . '/../database/schema.sql';
$mysqlBin = 'C:\\xampp\\mysql\\bin\\mysql.exe';
$command = sprintf('cmd /c "%s -uroot < "%s" 2>&1"', $mysqlBin, $schemaPath);
$output = [];
$status = 0;

exec($command, $output, $status);

if ($status !== 0) {
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo "Database setup failed.\n";
    echo implode(PHP_EOL, $output);
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo "Database setup completed successfully.\n";
if (!empty($output)) {
    echo implode(PHP_EOL, $output);
}
