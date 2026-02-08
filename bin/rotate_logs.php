#!/usr/bin/env php
<?php
/**
 * Log Rotation Script
 *
 * Rotates and compresses old log files to prevent disk space issues.
 * Run this script daily via cron.
 *
 * Usage:
 *   php bin/rotate_logs.php [--days=7]
 */

$logDir = __DIR__ . '/../storage/logs';
$maxDays = 7; // Keep logs for 7 days by default

// Parse CLI arguments
foreach ($argv as $arg) {
    if (strpos($arg, '--days=') === 0) {
        $maxDays = (int) substr($arg, 7);
    }
}

echo "=== Log Rotation Script ===\n";
echo "Log directory: $logDir\n";
echo "Retention period: $maxDays days\n\n";

if (!is_dir($logDir)) {
    echo "Error: Log directory does not exist.\n";
    exit(1);
}

$cutoffDate = time() - ($maxDays * 86400);
$deleted = 0;
$compressed = 0;

// Scan log directory
$files = scandir($logDir);
foreach ($files as $file) {
    if ($file === '.' || $file === '..') {
        continue;
    }

    $filePath = $logDir . '/' . $file;

    // Skip if not a file
    if (!is_file($filePath)) {
        continue;
    }

    $fileTime = filemtime($filePath);

    // Handle daily logs (app-YYYY-MM-DD.log)
    if (preg_match('/^app-(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
        $logDate = $matches[1];

        // If log is older than cutoff, delete it
        if ($fileTime < $cutoffDate) {
            if (unlink($filePath)) {
                echo "Deleted: $file\n";
                $deleted++;
            }
        }
        // If log is from yesterday or older (but not past cutoff), compress it
        elseif ($logDate < date('Y-m-d')) {
            $gzPath = $filePath . '.gz';
            if (!file_exists($gzPath)) {
                $data = file_get_contents($filePath);
                $gz = gzopen($gzPath, 'wb9');
                if ($gz) {
                    gzwrite($gz, $data);
                    gzclose($gz);
                    unlink($filePath);
                    echo "Compressed: $file\n";
                    $compressed++;
                }
            }
        }
    }

    // Delete old compressed logs
    if (preg_match('/^app-(\d{4}-\d{2}-\d{2})\.log\.gz$/', $file) && $fileTime < $cutoffDate) {
        if (unlink($filePath)) {
            echo "Deleted: $file\n";
            $deleted++;
        }
    }
}

echo "\n=== Summary ===\n";
echo "Compressed: $compressed files\n";
echo "Deleted: $deleted files\n";
echo "Done.\n";

exit(0);
