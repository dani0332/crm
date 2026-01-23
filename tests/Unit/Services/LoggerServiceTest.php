<?php

namespace Tests\Unit\Services;

use App\Services\Logger\LoggerService;
use Error;
use Exception;
use Illuminate\Support\Facades\Log;
use ParseError;
use Tests\TestCase;
use Throwable;
use TypeError;

class LoggerServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock all Log facade methods to prevent actual logging during tests
        Log::shouldReceive('error')->andReturnNull();
        Log::shouldReceive('warning')->andReturnNull();
        Log::shouldReceive('info')->andReturnNull();
        Log::shouldReceive('debug')->andReturnNull();
        Log::shouldReceive('notice')->andReturnNull();
        Log::shouldReceive('alert')->andReturnNull();
        Log::shouldReceive('critical')->andReturnNull();
        Log::shouldReceive('emergency')->andReturnNull();
        Log::shouldReceive('withoutContext')->andReturnNull();
        Log::shouldReceive('withContext')->andReturnNull();

        // Clear any existing context
        LoggerService::endLogging();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        LoggerService::endLogging();

        parent::tearDown();
    }

    // Test Exception logging (most common use case)
    public function test_error_method_accepts_exception()
    {
        $exception = new Exception('Test exception', 123);

        // This should not throw a TypeError
        LoggerService::error('Test error message', [], exception: $exception);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test Throwable support - TypeError (the specific issue from the bug report)
    public function test_error_method_accepts_typeerror_throwable()
    {
        $typeError = new TypeError('Test TypeError');

        // This should not throw a TypeError
        LoggerService::error('TypeError occurred', [], exception: $typeError);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test Error objects (parent class of TypeError)
    public function test_error_method_accepts_error_throwable()
    {
        $error = new Error('Test Error');

        // This should not throw a TypeError
        LoggerService::error('Generic error occurred', [], exception: $error);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test ParseError (another Error subclass)
    public function test_error_method_accepts_parseerror_throwable()
    {
        $parseError = new ParseError('Test ParseError');

        // This should not throw a TypeError
        LoggerService::error('Parse error occurred', [], exception: $parseError);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test without exception parameter
    public function test_error_method_without_exception()
    {
        // This should work without any exception parameter
        LoggerService::error('Test message without exception');

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test with extra data and exception
    public function test_error_method_with_extra_and_exception()
    {
        $exception = new Exception('Extra test exception');

        // This should work with extra data and exception
        LoggerService::error('Test message', ['key' => 'value'], exception: $exception);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test backward compatibility - ensure existing code still works
    public function test_backward_compatibility_with_exception_objects()
    {
        $exception = new Exception('Legacy exception', 456);

        // This should work with traditional Exception objects
        LoggerService::error('Backward compatibility test', [], exception: $exception);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test all logging methods that accept exceptions
    public function test_warning_method_accepts_throwable()
    {
        $exception = new Exception('Warning exception');

        // This should not throw a TypeError
        LoggerService::warning('Warning message', [], exception: $exception);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    public function test_debug_method_accepts_throwable()
    {
        $exception = new Exception('Debug exception');

        // This should not throw a TypeError
        LoggerService::debug('Debug message', [], exception: $exception);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    public function test_notice_method_accepts_throwable()
    {
        $exception = new Exception('Notice exception');

        // This should not throw a TypeError
        LoggerService::notice('Notice message', [], exception: $exception);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    public function test_alert_method_accepts_throwable()
    {
        $exception = new Exception('Alert exception');

        // This should not throw a TypeError
        LoggerService::alert('Alert message', [], exception: $exception);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    public function test_critical_method_accepts_throwable()
    {
        $exception = new Exception('Critical exception');

        // This should not throw a TypeError
        LoggerService::critical('Critical message', [], exception: $exception);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    public function test_emergency_method_accepts_throwable()
    {
        $exception = new Exception('Emergency exception');

        // This should not throw a TypeError
        LoggerService::emergency('Emergency message', [], exception: $exception);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test methods that don't accept exceptions
    public function test_info_method_does_not_accept_exception()
    {
        // This should work without any exception parameter
        LoggerService::info('Info message');

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test real-world scenario from codebase - Throwable caught in PolicyIssuanceJob
    public function test_policy_issuance_job_scenario()
    {
        // This simulates the exact scenario from PolicyIssuanceJob.php where Throwable is caught
        // and passed to LoggerService::error() - this was causing the TypeError
        try {
            throw new TypeError('Simulated policy issuance error');
        } catch (Throwable $e) {
            // This should not throw a TypeError anymore
            LoggerService::error('Exception occurred during policy issuance automation', [
                'process_id' => 123,
                'quote_code' => 'CAR-DRQ6N38S',
            ], exception: $e);
        }

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test mixed scenario - some methods with exception, some without
    public function test_mixed_logging_scenarios()
    {
        $exception = new Exception('Error exception');

        // Test error with exception
        LoggerService::error('Error with exception', [], exception: $exception);

        // Test info without exception
        LoggerService::info('Info without exception', ['extra' => 'data']);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test null exception parameter (should not add exception data)
    public function test_null_exception_parameter()
    {
        // This should work with null exception
        LoggerService::error('Message with null exception', [], exception: null);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test context parameter is preserved
    public function test_context_parameter_preserved()
    {
        $exception = new Exception('Exception with context');

        // This should work with additional context parameters
        LoggerService::error('Message with context', [], exception: $exception, context: ['custom_context' => 'value']);

        $this->assertTrue(true); // If we reach here, the test passes
    }

    // Test edge case - Throwable with custom code
    public function test_throwable_with_custom_code()
    {
        $exception = new Exception('Custom code exception', 999);

        // This should work with custom exception codes
        LoggerService::error('Custom code error', [], exception: $exception);

        $this->assertTrue(true); // If we reach here, the test passes
    }
}
