#!/usr/bin/env php
<?php
/**
 * Release Helper Script
 *
 * This script provides guided steps for release tasks (items 23-26).
 * It automates what can be automated and provides instructions for manual steps.
 *
 * Usage:
 *   php bin/release_helper.php <command>
 *
 * Commands:
 *   prepare    - Prepare for release (pre-flight checks)
 *   export     - Export release artifacts (mysql.sql)
 *   checklist  - Show release checklist
 */

require_once __DIR__ . '/../src/autoload.php';

$command = $argv[1] ?? 'checklist';

echo "=== Release Helper (v0.1) ===\n\n";

switch ($command) {
    case 'prepare':
        echo "PREPARING FOR RELEASE\n";
        echo str_repeat('=', 60) . "\n\n";

        // Check 1: All TODOs done
        echo "[1/5] Checking TODO status...\n";
        passthru("php " . escapeshellarg(__DIR__ . '/check_full_todos_done.php'), $exitCode);
        if ($exitCode === 0) {
            echo "   ✅ PASS: All implementation TODOs complete\n";
        } else {
            echo "   ⚠️  WARN: Some TODOs remain (items 22-26 are expected)\n";
        }
        echo "\n";

        // Check 2: No syntax errors
        echo "[2/5] Running PHP lint...\n";
        passthru("php " . escapeshellarg(__DIR__ . '/php_lint.php'), $exitCode);
        if ($exitCode === 0) {
            echo "   ✅ PASS: No PHP syntax errors\n";
        } else {
            echo "   ❌ FAIL: PHP syntax errors found\n";
        }
        echo "\n";

        // Check 3: Migration status
        echo "[3/5] Checking migration status...\n";
        passthru("php " . escapeshellarg(__DIR__ . '/migrate_status.php'), $exitCode);
        if ($exitCode === 0) {
            echo "   ✅ PASS: No pending migrations\n";
        } else {
            echo "   ❌ FAIL: Pending migrations or error\n";
        }
        echo "\n";

        // Check 4: Git status
        echo "[4/5] Checking git status...\n";
        exec("git status --porcelain", $output, $exitCode);
        if (empty($output)) {
            echo "   ✅ PASS: Working directory clean\n";
        } else {
            echo "   ⚠️  WARN: Uncommitted changes:\n";
            foreach ($output as $line) {
                echo "      $line\n";
            }
        }
        echo "\n";

        // Check 5: Version tag
        echo "[5/5] Checking for version tag...\n";
        exec("git tag --list 'v0.1*'", $output, $exitCode);
        if (!empty($output)) {
            echo "   ℹ️  INFO: Existing v0.1 tags found:\n";
            foreach ($output as $tag) {
                echo "      $tag\n";
            }
        } else {
            echo "   ℹ️  INFO: No v0.1 tags yet (will create during release)\n";
        }
        echo "\n";

        echo "Pre-flight checks complete.\n";
        echo "Review the output above and fix any failures before proceeding.\n\n";
        break;

    case 'export':
        echo "EXPORTING RELEASE ARTIFACTS\n";
        echo str_repeat('=', 60) . "\n\n";

        echo "This will export the final mysql.sql for v0.1 release.\n\n";

        $dbType = getenv('DB_TYPE') ?: 'mysql';
        if ($dbType !== 'mysql') {
            echo "⚠️  WARNING: DB_TYPE is not 'mysql'. This export is for MySQL only.\n";
            echo "Press Enter to continue or Ctrl+C to abort: ";
            fgets(STDIN);
        }

        $dbName = getenv('DB_NAME') ?: 'ss_ltd';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASSWORD') ?: '';

        echo "Exporting database: $dbName\n";
        echo "Output file: mysql.sql\n\n";

        $mysqldump = 'mysqldump';
        // Try common paths for Windows XAMPP
        if (file_exists('c:/xampp/mysql/bin/mysqldump.exe')) {
            $mysqldump = 'c:/xampp/mysql/bin/mysqldump.exe';
        }

        $cmd = escapeshellcmd($mysqldump) . " -u " . escapeshellarg($dbUser);
        if (!empty($dbPass)) {
            $cmd .= " -p" . escapeshellarg($dbPass);
        }
        $cmd .= " " . escapeshellarg($dbName) . " > mysql.sql 2>&1";

        echo "Running: mysqldump...\n";
        passthru($cmd, $exitCode);

        if ($exitCode === 0 && file_exists(__DIR__ . '/../mysql.sql')) {
            $size = filesize(__DIR__ . '/../mysql.sql');
            echo "\n✅ Export complete: mysql.sql (" . number_format($size) . " bytes)\n";
        } else {
            echo "\n❌ Export failed or file not created\n";
            echo "Try manually: mysqldump -u root ss_ltd > mysql.sql\n";
        }
        echo "\n";
        break;

    case 'checklist':
    default:
        echo "RELEASE CHECKLIST (Items 23-26)\n";
        echo str_repeat('=', 60) . "\n\n";

        echo "□ Item 22: Run manual QA checklist\n";
        echo "     Run: php bin/qa_prerelease.php\n";
        echo "     Fix any blockers found during testing\n\n";

        echo "□ Item 23: Release preparation\n";
        echo "     - Review completed QA results\n";
        echo "     - Document known issues or limitations (see docs/v0.1_scope_freeze.md)\n";
        echo "     - Get stakeholder sign-off\n";
        echo "     - Run: php bin/release_helper.php prepare\n\n";

        echo "□ Item 24: Tag version + export artifacts\n";
        echo "     - Create git tag: git tag -a v0.1.0 -m 'Release v0.1.0 - Operational MVP'\n";
        echo "     - Push tag: git push origin v0.1.0\n";
        echo "     - Export mysql.sql: php bin/release_helper.php export\n";
        echo "     - Build Android signed APK/AAB:\n";
        echo "       cd android && ./gradlew assembleRelease\n";
        echo "       (or bundleRelease for Play Store)\n\n";

        echo "□ Item 25: Deploy to production\n";
        echo "     - Upload code to production server\n";
        echo "     - Create .env (based on .env.example) with production config\n";
        echo "     - Set APP_DEBUG=0, APP_ENV=prod, unique APP_KEY\n";
        echo "     - Run migrations: php bin/migrate.php --env=.env\n";
        echo "     - Create admin user: php bin/create_admin_user.php --env=.env\n";
        echo "     - Verify health: curl https://yoursite.com/health\n";
        echo "     - Verify health/db: curl https://yoursite.com/health/db\n";
        echo "     - Smoke test: login, create lead, view project\n\n";

        echo "□ Item 26: Post-release monitoring\n";
        echo "     - Monitor storage/logs/ for errors\n";
        echo "     - Track user feedback and issues\n";
        echo "     - Create GitHub issues for bugs\n";
        echo "     - Plan v0.1.1 hotfix if critical issues found\n";
        echo "     - Plan v0.2 features (see full_todos.md backlog)\n\n";

        echo "HELPFUL COMMANDS:\n";
        echo "  php bin/release_helper.php prepare   - Run pre-flight checks\n";
        echo "  php bin/release_helper.php export    - Export mysql.sql\n";
        echo "  php bin/qa_prerelease.php            - Run manual QA guide\n\n";

        echo "See docs/v0.1_scope_freeze.md for acceptance criteria.\n";
        echo "See full_todos.md for complete release plan.\n\n";
        break;
}

exit(0);
