#!/usr/bin/env php
<?php
/**
 * QA: Automated Ops Checklist Runner
 *
 * This script automates the "Ops" section of the manual test checklist.
 * Run this before manual testing to verify operational health.
 *
 * Usage:
 *   php bin/qa_ops_checklist.php [--env=.env.staging]
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Database;

// Parse CLI args
$envFile = '.env';
foreach ($argv as $arg) {
    if (strpos($arg, '--env=') === 0) {
        $envFile = substr($arg, 6);
    }
}

// Load environment
if (file_exists(__DIR__ . '/../' . $envFile)) {
    $lines = file(__DIR__ . '/../' . $envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        putenv(trim($line));
    }
}

echo "=== QA: Ops Checklist Runner ===\n";
echo "Using env file: $envFile\n\n";

$allPassed = true;

// Test 1: Health endpoint
echo "[1/7] Testing /health endpoint...\n";
try {
    $baseUrl = getenv('APP_URL') ?: 'http://localhost:8000';
    $healthUrl = rtrim($baseUrl, '/') . '/health';

    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents($healthUrl, false, $context);

    if ($response === false || strpos($response, 'OK') === false) {
        echo "   ❌ FAIL: /health did not return OK\n";
        $allPassed = false;
    } else {
        echo "   ✅ PASS: /health returns OK\n";
    }
} catch (Exception $e) {
    echo "   ❌ FAIL: /health endpoint error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 2: Health DB endpoint
echo "[2/7] Testing /health/db endpoint...\n";
try {
    $baseUrl = getenv('APP_URL') ?: 'http://localhost:8000';
    $healthDbUrl = rtrim($baseUrl, '/') . '/health/db';

    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents($healthDbUrl, false, $context);

    if ($response === false || strpos($response, 'OK') === false) {
        echo "   ❌ FAIL: /health/db did not return OK\n";
        $allPassed = false;
    } else {
        echo "   ✅ PASS: /health/db returns OK\n";
    }
} catch (Exception $e) {
    echo "   ❌ FAIL: /health/db endpoint error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 3: DB connection (bin/health_db.php)
echo "[3/7] Testing direct DB connection...\n";
try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT 1");
    if ($stmt->fetch()) {
        echo "   ✅ PASS: Direct DB connection OK\n";
    } else {
        echo "   ❌ FAIL: DB query did not return expected result\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ FAIL: DB connection error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 4: Migration status (bin/migrate_status.php)
echo "[4/7] Testing migration status...\n";
try {
    ob_start();
    passthru("php " . escapeshellarg(__DIR__ . '/migrate_status.php') . " --env=" . escapeshellarg($envFile) . " 2>&1", $exitCode);
    $output = ob_get_clean();

    if ($exitCode === 0 && strpos($output, 'pending=0') !== false) {
        echo "   ✅ PASS: No pending migrations\n";
    } else {
        echo "   ❌ FAIL: Pending migrations found or error\n";
        echo "   Output: " . trim($output) . "\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "   ❌ FAIL: Migration status error: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 5: Large files QA (optional but recommended)
echo "[5/7] Testing large file upload/download boundaries...\n";
if (file_exists(__DIR__ . '/qa_large_files.php')) {
    try {
        ob_start();
        passthru("php " . escapeshellarg(__DIR__ . '/qa_large_files.php') . " --env=" . escapeshellarg($envFile) . " 2>&1", $exitCode);
        $output = ob_get_clean();

        if ($exitCode === 0 && strpos($output, 'All large file tests passed') !== false) {
            echo "   ✅ PASS: Large file tests passed\n";
        } else {
            echo "   ⚠️  WARN: Large file tests did not fully pass\n";
            echo "   Output: " . trim($output) . "\n";
            // Not failing overall - this is optional
        }
    } catch (Exception $e) {
        echo "   ⚠️  WARN: Large file tests error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ⚠️  SKIP: qa_large_files.php not found\n";
}

// Test 6: Dev tools QA (optional, only in debug mode)
echo "[6/7] Testing dev tools endpoints (if debug enabled)...\n";
if (file_exists(__DIR__ . '/qa_dev_tools.php') && getenv('APP_DEBUG') === '1') {
    try {
        ob_start();
        passthru("php " . escapeshellarg(__DIR__ . '/qa_dev_tools.php') . " --env=" . escapeshellarg($envFile) . " 2>&1", $exitCode);
        $output = ob_get_clean();

        if ($exitCode === 0 && strpos($output, 'All dev tools tests passed') !== false) {
            echo "   ✅ PASS: Dev tools tests passed\n";
        } else {
            echo "   ⚠️  WARN: Dev tools tests did not fully pass\n";
            echo "   Output: " . trim($output) . "\n";
            // Not failing overall - this is optional
        }
    } catch (Exception $e) {
        echo "   ⚠️  WARN: Dev tools tests error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ⏭️  SKIP: Dev tools tests (APP_DEBUG=0 or qa_dev_tools.php not found)\n";
}

// Test 7: RC1 staging validation (optional but recommended)
echo "[7/7] Testing RC1 local staging simulation...\n";
if (file_exists(__DIR__ . '/rc1_local_staging.php')) {
    echo "   ⚠️  SKIP: RC1 staging test requires manual run with separate DB\n";
    echo "   Run manually: php bin/rc1_local_staging.php --env=.env.staging\n";
} else {
    echo "   ⚠️  SKIP: rc1_local_staging.php not found\n";
}

// Summary
echo "\n=== Summary ===\n";
if ($allPassed) {
    echo "✅ All critical ops checks PASSED\n";
    echo "You can proceed with manual QA testing.\n";
    exit(0);
} else {
    echo "❌ Some ops checks FAILED\n";
    echo "Fix issues before proceeding with manual QA.\n";
    exit(1);
}
