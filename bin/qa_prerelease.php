#!/usr/bin/env php
<?php
/**
 * Pre-Release QA Guide
 *
 * This script provides a guided walkthrough for completing the manual QA checklist.
 * It automates what can be automated and provides instructions for manual steps.
 *
 * Usage:
 *   php bin/qa_prerelease.php [--env=.env.staging]
 */

require_once __DIR__ . '/../src/autoload.php';

// Parse CLI args
$envFile = '.env';
foreach ($argv as $arg) {
    if (strpos($arg, '--env=') === 0) {
        $envFile = substr($arg, 6);
    }
}

echo "=== Pre-Release QA Guide (v0.1) ===\n\n";

echo "This script helps you complete item 22 of the release plan:\n";
echo "  'Run manual QA checklist + fix blockers (docs/manual_test_checklist.md)'\n\n";

echo "PREREQUISITES:\n";
echo "  1. Fresh staging-like environment (separate DB, fresh uploads folder)\n";
echo "  2. Server running (e.g., php -S 127.0.0.1:8000 index.php)\n";
echo "  3. Android app built and installed on test device (optional for now)\n\n";

// Step 1: Run automated ops checks
echo "=== STEP 1: Automated Ops Checks ===\n\n";
echo "Running automated ops checklist...\n\n";

passthru("php " . escapeshellarg(__DIR__ . '/qa_ops_checklist.php') . " --env=" . escapeshellarg($envFile), $exitCode);

if ($exitCode !== 0) {
    echo "\n❌ Automated ops checks failed. Fix issues before proceeding.\n";
    exit(1);
}

echo "\n✅ Automated ops checks passed.\n\n";

// Step 2: Manual testing guide
echo "=== STEP 2: Manual Testing Guide ===\n\n";

$checklist = [
    'Web: Auth' => [
        'Register new client account and verify redirect to portal',
        'Login with existing user (admin, pm, employee, client)',
        'Logout and verify session is invalidated',
        'Request password reset for existing email',
        'Use reset link, set new password, login with new password',
    ],
    'Web: Quote Request' => [
        'Test quote request mode chooser (Simple vs Advanced)',
        'Simple mode: fill required fields (name/email/consent/description) and submit',
        'Advanced mode: fill all fields including address/scope and submit',
        'Test validation: submit with invalid/missing fields and verify errors',
        'Upload attachments: JPG/PNG/PDF within size limits',
        'Try uploading disallowed file type and verify rejection',
    ],
    'Web: Leads' => [
        'Verify leads list shows pagination',
        'Open lead detail and verify metadata and attachments display',
        'Assign PM to lead (admin only)',
        'Change lead status and verify audit log entry created',
        'Convert lead to project and verify redirect to project detail',
        'Check that lead view shows link to created project',
    ],
    'Web: Projects' => [
        'Verify projects list shows pagination',
        'Open project detail and verify overview tab displays',
        'Change project status and verify audit log written',
        'Check project members view respects role restrictions (if enabled)',
    ],
    'Web: Messages' => [
        'Open inbox and verify threads list with pagination',
        'Verify unread badge appears for unread threads',
        'Open thread and verify it marks as read',
        'Send a message and verify it appears in thread',
        'Verify message marks as read for sender',
    ],
    'Web: Uploads ACL' => [
        'Login as staff, upload file to lead, verify can download',
        'Upload file to project, verify can download',
        'Login as client, verify CANNOT download staff file (client_visible=0)',
        'Staff enables client_visible flag',
        'Client verifies CAN now download the file',
        'Login as different client, verify CANNOT download other client files',
    ],
    'Android: Field App' => [
        'Login and verify token is persisted',
        'Toggle airplane mode and verify offline banner appears',
        'Verify API calls fail gracefully (no crash)',
        'Take photo, attach to project, verify queued when offline',
        'Go back online, verify WorkManager uploads queued item',
        'Open thread, send text message',
        'Attach photo/file and verify upload',
        'Start timesheet and verify timer increments',
        'Stop timesheet and verify entry appears in list',
    ],
];

$section = 1;
$totalSections = count($checklist);

foreach ($checklist as $category => $tasks) {
    echo "[$section/$totalSections] $category\n";
    echo str_repeat('-', 60) . "\n";

    foreach ($tasks as $i => $task) {
        echo "  " . ($i + 1) . ". $task\n";
    }

    echo "\nPress Enter when you have completed this section (or Ctrl+C to abort): ";
    fgets(STDIN);
    echo "\n";

    $section++;
}

// Step 3: Final summary
echo "=== STEP 3: Final Summary ===\n\n";

echo "✅ Manual QA checklist completed!\n\n";

echo "NEXT STEPS (Release Plan items 23-26):\n";
echo "  23. Prepare release:\n";
echo "      - Review all completed checklist items\n";
echo "      - Document any known issues or workarounds\n";
echo "      - Get sign-off from stakeholders\n\n";
echo "  24. Tag version + export artifacts:\n";
echo "      - git tag v0.1.0\n";
echo "      - Export final mysql.sql: mysqldump -u root ss_ltd > mysql.sql\n";
echo "      - Build signed Android APK/AAB\n\n";
echo "  25. Deploy to production:\n";
echo "      - Upload code to production server\n";
echo "      - Run migrations on production DB\n";
echo "      - Verify health endpoints\n";
echo "      - Smoke test critical paths\n\n";
echo "  26. Post-release monitoring:\n";
echo "      - Monitor logs for errors\n";
echo "      - Track user feedback\n";
echo "      - Plan v0.1.1 hotfix if needed\n\n";

echo "See docs/v0.1_scope_freeze.md for acceptance criteria.\n";
echo "See full_todos.md for complete release plan.\n\n";

exit(0);
