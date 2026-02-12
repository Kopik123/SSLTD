<?php
declare(strict_types=1);

/**
 * Vercel Serverless Function Entry Point
 * 
 * This file wraps the main index.php to work with Vercel's serverless environment.
 * All requests are routed through here when deployed on Vercel.
 */

// Vercel specific: set working directory to project root
chdir(__DIR__ . '/..');

// Check if running on Vercel
$isVercel = getenv('VERCEL') === '1' || getenv('NOW_REGION') !== false;

if ($isVercel) {
    // Vercel-specific configuration
    // File uploads must use /tmp (ephemeral storage)
    putenv('UPLOAD_TMP_DIR=/tmp/uploads');
    
    // Ensure storage directories exist in /tmp
    @mkdir('/tmp/logs', 0755, true);
    @mkdir('/tmp/uploads', 0755, true);
    @mkdir('/tmp/sessions', 0755, true);
    
    // Override storage paths for Vercel
    putenv('STORAGE_LOGS_PATH=/tmp/logs');
    putenv('STORAGE_UPLOADS_PATH=/tmp/uploads');
    putenv('SESSION_SAVE_PATH=/tmp/sessions');
}

// Include the main application entry point
require __DIR__ . '/../index.php';
