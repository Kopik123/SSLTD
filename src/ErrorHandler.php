<?php

namespace App;

/**
 * Central Error Handler
 *
 * Provides centralized error and exception handling with structured logging.
 * Captures errors, exceptions, and fatal errors for debugging and monitoring.
 */
class ErrorHandler
{
    private static bool $initialized = false;
    private static string $logDir;
    private static bool $isProduction;

    /**
 * Initialize error handler
     *
     * Sets up error, exception, and shutdown handlers.
     * Call this early in your application bootstrap.
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$logDir = __DIR__ . '/../storage/logs';
        self::$isProduction = (getenv('APP_ENV') === 'prod' || getenv('APP_ENV') === 'production');

        // Ensure log directory exists
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }

        // Set error handler
        set_error_handler([self::class, 'handleError']);

        // Set exception handler
        set_exception_handler([self::class, 'handleException']);

        // Set shutdown handler for fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$initialized = true;
    }

    /**
     * Error handler callback
     *
     * @param int $errno Error level
     * @param string $errstr Error message
     * @param string $errfile File where error occurred
     * @param int $errline Line number where error occurred
     * @return bool
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Don't handle errors suppressed with @
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorType = self::getErrorType($errno);

        self::log('error', $errstr, [
            'type' => $errorType,
            'errno' => $errno,
            'file' => $errfile,
            'line' => $errline,
            'trace' => self::getSimplifiedTrace(debug_backtrace())
        ]);

        // Don't execute PHP internal error handler
        return true;
    }

    /**
     * Exception handler callback
     *
     * @param \Throwable $exception
     */
    public static function handleException(\Throwable $exception): void
    {
        self::log('exception', $exception->getMessage(), [
            'class' => get_class($exception),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => self::getSimplifiedTrace($exception->getTrace())
        ]);

        // Display user-friendly error in production
        if (self::$isProduction) {
            http_response_code(500);
            if (php_sapi_name() === 'cli') {
                echo "An error occurred. Please check the logs.\n";
            } else {
                echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
                echo '<h1>500 Internal Server Error</h1>';
                echo '<p>An unexpected error occurred. Please try again later.</p>';
                echo '</body></html>';
            }
        } else {
            // Show detailed error in development
            http_response_code(500);
            if (php_sapi_name() === 'cli') {
                echo "Exception: " . $exception->getMessage() . "\n";
                echo "File: " . $exception->getFile() . ":" . $exception->getLine() . "\n";
                echo $exception->getTraceAsString() . "\n";
            } else {
                echo '<pre>';
                echo get_class($exception) . ': ' . $exception->getMessage() . "\n\n";
                echo 'File: ' . $exception->getFile() . ':' . $exception->getLine() . "\n\n";
                echo $exception->getTraceAsString();
                echo '</pre>';
            }
        }

        exit(1);
    }

    /**
     * Shutdown handler for fatal errors
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::log('fatal', $error['message'], [
                'type' => self::getErrorType($error['type']),
                'file' => $error['file'],
                'line' => $error['line']
            ]);

            if (self::$isProduction) {
                http_response_code(500);
                if (php_sapi_name() !== 'cli') {
                    echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
                    echo '<h1>500 Internal Server Error</h1>';
                    echo '<p>An unexpected error occurred. Please try again later.</p>';
                    echo '</body></html>';
                }
            }
        }
    }

    /**
     * Log an error, warning, or info message
     *
     * @param string $level Log level (error, warning, info, debug)
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $logFile = self::$logDir . '/app.log';

        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'request_id' => $_SERVER['REQUEST_ID'] ?? self::generateRequestId(),
            'url' => $_SERVER['REQUEST_URI'] ?? 'cli',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'cli'
        ];

        $logLine = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

        // Use file_put_contents with FILE_APPEND and LOCK_EX for atomic writes
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

        // Also log to daily file for easier rotation
        $dailyLogFile = self::$logDir . '/app-' . date('Y-m-d') . '.log';
        @file_put_contents($dailyLogFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get error type name from error number
     *
     * @param int $errno Error number
     * @return string Error type name
     */
    private static function getErrorType(int $errno): string
    {
        $errorTypes = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        ];

        return $errorTypes[$errno] ?? 'UNKNOWN';
    }

    /**
     * Get simplified stack trace for logging
     *
     * @param array $trace Debug backtrace
     * @return array Simplified trace
     */
    private static function getSimplifiedTrace(array $trace): array
    {
        $simplified = [];

        foreach (array_slice($trace, 0, 10) as $frame) {
            $simplified[] = [
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '')
            ];
        }

        return $simplified;
    }

    /**
     * Generate unique request ID
     *
     * @return string Request ID
     */
    private static function generateRequestId(): string
    {
        return substr(md5(uniqid('', true)), 0, 16);
    }

    /**
     * Check if error handler is initialized
     *
     * @return bool
     */
    public static function isInitialized(): bool
    {
        return self::$initialized;
    }
}
