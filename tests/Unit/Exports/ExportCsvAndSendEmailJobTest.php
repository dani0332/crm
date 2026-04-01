<?php

use App\Jobs\ExportCsvAndSendEmailJob;
use App\Jobs\Middleware\FreshRequest;

/**
 * Dummy export class used only for this test.
 * It asserts that the current container Request contains the expected 'foo' value
 * when sendEmailWithCSVAttachment is invoked.
 */
class DummyExportForTest
{
    public static ?string $expectedFoo = null;
    public static bool $called = false;
    public static bool $expectHasOnlyFirst = false;

    public function sendEmailWithCSVAttachment(string $recipient, string $subject, array $requestParams, array $extra = [], string $fileName = ''): void
    {
        $actual = request()->input('foo');

        if ($actual !== self::$expectedFoo) {
            throw new Exception("Unexpected request foo value. Actual: {$actual}; Expected: ".self::$expectedFoo);
        }
        $hasOnlyFirst = request()->has('only_first');
        if ($hasOnlyFirst !== self::$expectHasOnlyFirst) {
            throw new Exception('only_first presence mismatch. Actual: '.($hasOnlyFirst ? 'present' : 'absent').'; Expected: '.(self::$expectHasOnlyFirst ? 'present' : 'absent'));
        }

        self::$called = true;
    }
}

it('does not leak request parameters between sequential export jobs', function () {
    // Ensure no pre-existing request data
    request()->replace([]);

    $middleware = new FreshRequest;

    $paramsOne = [
        'foo' => 'one',
        'only_first' => 'secret',
        'fileName' => 'test-one.csv',
        'recipientEmail' => 'one@example.test',
        'subject' => 'Test One',
        'quoteType' => 'bike',
    ];

    $paramsTwo = [
        'foo' => 'two',
        'fileName' => 'test-two.csv',
        'recipientEmail' => 'two@example.test',
        'subject' => 'Test Two',
        'quoteType' => 'bike',
    ];

    // First job: expect 'one'
    DummyExportForTest::$expectedFoo = 'one';
    DummyExportForTest::$expectHasOnlyFirst = true;
    DummyExportForTest::$called = false;

    $jobOne = new ExportCsvAndSendEmailJob(DummyExportForTest::class, $paramsOne['recipientEmail'], $paramsOne);
    // Simulate the queue job wrapper that provides getJobId() and attempts()
    $jobOne->job = new class
    {
        public function getJobId()
        {
            return 'test-job-1';
        }
        public function attempts()
        {
            return 1;
        }
    };

    $next = function ($job) {
        $job->handle();

        return null;
    };

    $middleware->handle($jobOne, $next);
    expect(DummyExportForTest::$called)->toBeTrue();

    // Reset and run second job sequentially in same process
    DummyExportForTest::$expectedFoo = 'two';
    DummyExportForTest::$called = false;
    DummyExportForTest::$expectHasOnlyFirst = false;

    $jobTwo = new ExportCsvAndSendEmailJob(DummyExportForTest::class, $paramsTwo['recipientEmail'], $paramsTwo);
    $jobTwo->job = new class
    {
        public function getJobId()
        {
            return 'test-job-2';
        }
        public function attempts()
        {
            return 1;
        }
    };
    $middleware->handle($jobTwo, $next);
    expect(DummyExportForTest::$called)->toBeTrue();
});
