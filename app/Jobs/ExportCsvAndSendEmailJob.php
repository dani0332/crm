<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExportCsvAndSendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 300 (5 minutes) 900 (15 minutes)
    public $tries = 2;
    public $backoff = 30;
    private $exportClass;
    private $recipientEmail;
    private $requestParams;

    /**
     * Create a new job instance.
     *
     * @param  string  $exportClass  - The export class name (string, not instance)
     * @param  string  $recipientEmail  - Recipient email address
     * @param  array  $requestParams  - Clean array of parameters (no Request objects or models)
     */
    public function __construct(
        string $exportClass,
        string $recipientEmail,
        array $requestParams,
    ) {
        $this->exportClass = $exportClass;
        $this->recipientEmail = $recipientEmail;
        $this->requestParams = $requestParams;

        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {

        $jobId = $this->job->getJobId() ?? 'unknown';
        $startTime = microtime(true);
        $initialMemory = memory_get_usage(true) / 1024 / 1024;

        Log::info("CSV export job started for {$this->requestParams['fileName']}. Memory: {$initialMemory}MB, Attempt: {$this->attempts()}");

        try {
            // Instantiate the export class
            $exportInstance = app($this->exportClass);

            // Process CSV and send email
            $exportInstance->sendEmailWithCSVAttachment(
                $this->requestParams['recipientEmail'],
                $this->requestParams['subject'],
                $this->requestParams,
                [],
                $this->requestParams['fileName'],
            );

            $executionTime = round(microtime(true) - $startTime, 2);
            $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

            Log::info("CSV export job completed successfully for {$this->requestParams['fileName']}. Time: {$executionTime}s, Peak memory: {$peakMemory}MB");

        } catch (\Throwable $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

            Log::error("CSV export job [{$jobId}] failed after {$executionTime}s. Peak memory: {$peakMemory}MB. Exception: {$e->getMessage()}, {$e->getFile()}:{$e->getLine()}", [
                'trace' => collect($e->getTrace())->filter(function ($trace) {
                    return isset($trace['file']) && str_contains($trace['file'], '/app');
                })->all(),
            ]);

            throw $e;
        } finally {
            gc_collect_cycles();
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("CSV export job for {$this->requestParams['fileName']} has permanently failed: {$exception->getMessage()}");
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        $lockKey = md5(
            $this->exportClass.
            $this->recipientEmail
        );

        return [
            (new WithoutOverlapping($lockKey))
                ->dontRelease() // Don't release back to queue if locked
                ->expireAfter(300), // Lock expires after 5 mins (same as timeout)
        ];
    }
}
