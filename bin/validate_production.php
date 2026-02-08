#!/usr/bin/env php
<?php
/**
 * Production Environment Validation Script
 *
 * Validates that the production environment is properly configured and secure.
 * Run this before deploying to production to catch configuration issues.
 *
 * Usage: php bin/validate_production.php [--strict]
 */

require __DIR__ . '/../src/autoload.php';

use App\Database\Db;

$strict = in_array('--strict', $argv);
$errors = [];
$warnings = [];
$passed = 0;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  S&S LTD - Production Environment Validation                       ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Load .env file
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    $errors[] = ".env file not found at: $envPath";
    echo "❌ CRITICAL: .env file missing\n";
    exit(1);
}

$envVars = parse_ini_file($envPath);

function check($name, $condition, $errorMsg, $warningMsg = null) {
    global $errors, $warnings, $passed, $strict;

    echo "  Checking: $name ... ";

    if ($condition) {
        echo "✅ PASS\n";
        $passed++;
        return true;
    } else {
        if ($strict || $errorMsg) {
            echo "❌ FAIL\n";
            $errors[] = $errorMsg ?: $warningMsg;
        } else {
            echo "⚠️  WARN\n";
            $warnings[] = $warningMsg;
        }
        return false;
    }
}

echo "1. Environment Configuration\n";
echo str_repeat("-", 70) . "\n";

check(
    'APP_ENV is set to production',
    isset($envVars['APP_ENV']) && in_array($envVars['APP_ENV'], ['prod', 'production']),
    'APP_ENV must be set to "prod" or "production"'
);

check(
    'APP_DEBUG is disabled',
    isset($envVars['APP_DEBUG']) && $envVars['APP_DEBUG'] === '0',
    'APP_DEBUG must be set to 0 in production'
);

check(
    'APP_KEY is set',
    isset($envVars['APP_KEY']) && !empty($envVars['APP_KEY']),
    'APP_KEY must be set for encryption/sessions'
);

check(
    'APP_KEY is strong',
    isset($envVars['APP_KEY']) && strlen($envVars['APP_KEY']) >= 32,
    null,
    'APP_KEY should be at least 32 characters for strong encryption'
);

check(
    'APP_URL is set',
    isset($envVars['APP_URL']) && !empty($envVars['APP_URL']),
    null,
    'APP_URL should be set for proper link generation'
);

echo "\n2. Database Configuration\n";
echo str_repeat("-", 70) . "\n";

check(
    'DB_HOST is set',
    isset($envVars['DB_HOST']) && !empty($envVars['DB_HOST']),
    'DB_HOST must be configured'
);

check(
    'DB_NAME is set',
    isset($envVars['DB_NAME']) && !empty($envVars['DB_NAME']),
    'DB_NAME must be configured'
);

check(
    'DB_USER is set',
    isset($envVars['DB_USER']) && !empty($envVars['DB_USER']),
    'DB_USER must be configured'
);

check(
    'DB_PASSWORD is set',
    isset($envVars['DB_PASSWORD']),
    null,
    'DB_PASSWORD should be set (empty password is insecure)'
);

// Test database connection
try {
    $db = Db::getInstance();
    $result = $db->fetchOne('SELECT 1 as test');
    check(
        'Database connection successful',
        $result && $result['test'] == 1,
        'Unable to connect to database'
    );
} catch (Exception $e) {
    check(
        'Database connection successful',
        false,
        'Database connection failed: ' . $e->getMessage()
    );
}

echo "\n3. File System Permissions\n";
echo str_repeat("-", 70) . "\n";

$storageDir = __DIR__ . '/../storage';
$uploadsDir = __DIR__ . '/../storage/uploads';
$logsDir = __DIR__ . '/../storage/logs';

check(
    'storage/ directory exists',
    is_dir($storageDir),
    'storage/ directory must exist'
);

check(
    'storage/ is writable',
    is_writable($storageDir),
    'storage/ directory must be writable by web server'
);

check(
    'storage/uploads/ exists',
    is_dir($uploadsDir),
    'storage/uploads/ directory must exist'
);

check(
    'storage/uploads/ is writable',
    is_writable($uploadsDir),
    'storage/uploads/ directory must be writable'
);

check(
    'storage/logs/ exists',
    is_dir($logsDir),
    'storage/logs/ directory must exist'
);

check(
    'storage/logs/ is writable',
    is_writable($logsDir),
    'storage/logs/ directory must be writable'
);

// Check that storage is NOT publicly accessible
$htaccessPath = $storageDir . '/.htaccess';
check(
    'storage/ has .htaccess protection',
    file_exists($htaccessPath),
    null,
    'storage/.htaccess should exist to prevent direct web access'
);

echo "\n4. PHP Extensions & Requirements\n";
echo str_repeat("-", 70) . "\n";

check('PHP version >= 8.0', version_compare(PHP_VERSION, '8.0.0', '>='), 'PHP 8.0+ required');
check('PDO extension', extension_loaded('pdo'), 'PDO extension required');
check('PDO MySQL driver', extension_loaded('pdo_mysql'), 'pdo_mysql extension required');
check('mbstring extension', extension_loaded('mbstring'), 'mbstring extension required');
check('JSON extension', extension_loaded('json'), 'JSON extension required');
check('fileinfo extension', extension_loaded('fileinfo'), null, 'fileinfo recommended for file uploads');
check('GD or Imagick', extension_loaded('gd') || extension_loaded('imagick'), null, 'GD or Imagick recommended for image processing');

echo "\n5. Security Configuration\n";
echo str_repeat("-", 70) . "\n";

check(
    'display_errors is off',
    ini_get('display_errors') == 0,
    null,
    'display_errors should be off in production'
);

check(
    'error_reporting is appropriate',
    error_reporting() === (E_ALL & ~E_DEPRECATED & ~E_STRICT),
    null,
    'error_reporting should exclude notices in production'
);

check(
    'expose_php is off',
    ini_get('expose_php') == 0,
    null,
    'expose_php should be off to hide PHP version'
);

// Check for default/weak database credentials
if (isset($envVars['DB_USER']) && in_array(strtolower($envVars['DB_USER']), ['root', 'admin', 'test'])) {
    $warnings[] = 'Database user "' . $envVars['DB_USER'] . '" may be a default/insecure username';
    echo "  Checking: Database user is not default ... ⚠️  WARN\n";
} else {
    echo "  Checking: Database user is not default ... ✅ PASS\n";
    $passed++;
}

if (isset($envVars['DB_PASSWORD']) && (empty($envVars['DB_PASSWORD']) || in_array($envVars['DB_PASSWORD'], ['password', '123456', 'admin']))) {
    $warnings[] = 'Database password appears weak or is a common default';
    echo "  Checking: Database password is strong ... ⚠️  WARN\n";
} else {
    echo "  Checking: Database password is strong ... ✅ PASS\n";
    $passed++;
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  VALIDATION SUMMARY                                                ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "  ✅ Passed: " . $passed . "\n";
echo "  ⚠️  Warnings: " . count($warnings) . "\n";
echo "  ❌ Errors: " . count($errors) . "\n";
echo "\n";

if (!empty($warnings)) {
    echo "WARNINGS:\n";
    foreach ($warnings as $i => $warning) {
        echo "  " . ($i + 1) . ". " . $warning . "\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "ERRORS:\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". " . $error . "\n";
    }
    echo "\n";
    echo "❌ PRODUCTION VALIDATION FAILED\n";
    echo "   Fix the errors above before deploying to production.\n";
    echo "\n";
    exit(1);
}

if (!empty($warnings) && $strict) {
    echo "⚠️  STRICT MODE: Warnings present\n";
    echo "   Fix warnings above or run without --strict flag.\n";
    echo "\n";
    exit(1);
}

echo "✅ PRODUCTION VALIDATION PASSED\n";
echo "   Environment is ready for production deployment.\n";
echo "\n";

exit(0);
