<?php
/**
 * PHPUnit Bootstrap File
 *
 * This file is loaded before running tests.
 * Use it to set up autoloading and test environment.
 */

// Load application autoloader
require __DIR__ . '/../src/autoload.php';

// Set test environment variables
putenv('APP_ENV=testing');
putenv('APP_DEBUG=1');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');

// Initialize error handler for tests
use App\ErrorHandler;
ErrorHandler::init();
