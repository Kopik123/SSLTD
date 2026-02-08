<?php

namespace Tests\Unit;

use App\ErrorHandler;
use PHPUnit\Framework\TestCase;

/**
 * ErrorHandler Unit Tests
 */
class ErrorHandlerTest extends TestCase
{
    private string $logDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logDir = __DIR__ . '/../../storage/logs';

        // Ensure log directory exists
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function testInitialization(): void
    {
        // ErrorHandler should be initialized by bootstrap
        $this->assertTrue(ErrorHandler::isInitialized());
    }

    public function testLogCreatesFile(): void
    {
        $logFile = $this->logDir . '/app.log';

        // Remove log file if it exists
        if (file_exists($logFile)) {
            unlink($logFile);
        }

        // Log a test message
        ErrorHandler::log('info', 'Test log message', ['test' => true]);

        // Check that log file was created
        $this->assertFileExists($logFile);

        // Read and verify log content
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Test log message', $content);
        $this->assertStringContainsString('"level":"INFO"', $content);
        $this->assertStringContainsString('"test":true', $content);
    }

    public function testLogLevels(): void
    {
        $levels = ['error', 'warning', 'info', 'debug'];

        foreach ($levels as $level) {
            ErrorHandler::log($level, "Test {$level} message");
        }

        $logFile = $this->logDir . '/app.log';
        $content = file_get_contents($logFile);

        foreach ($levels as $level) {
            $this->assertStringContainsString(strtoupper($level), $content);
        }
    }

    public function testDailyLogFile(): void
    {
        $dailyLogFile = $this->logDir . '/app-' . date('Y-m-d') . '.log';

        ErrorHandler::log('info', 'Daily log test');

        $this->assertFileExists($dailyLogFile);

        $content = file_get_contents($dailyLogFile);
        $this->assertStringContainsString('Daily log test', $content);
    }

    public function testLogIncludesContext(): void
    {
        $context = [
            'user_id' => 123,
            'action' => 'test_action',
            'metadata' => ['key' => 'value']
        ];

        ErrorHandler::log('info', 'Context test', $context);

        $logFile = $this->logDir . '/app.log';
        $content = file_get_contents($logFile);

        $this->assertStringContainsString('"user_id":123', $content);
        $this->assertStringContainsString('"action":"test_action"', $content);
        $this->assertStringContainsString('"metadata":{"key":"value"}', $content);
    }
}
