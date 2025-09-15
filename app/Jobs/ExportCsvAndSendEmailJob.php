<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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
            // Instantiate the export class with constructor parameters if needed
            $exportInstance = $this->instantiateExportClass();

            if (empty($this->requestParams['user']) && ! empty($this->requestParams['user_id'])) {
                $this->requestParams['user'] = User::with(['permissions', 'roles.permissions'])->findOrFail($this->requestParams['user_id']);
            }

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
            // Always reset database connection back to default
            DB::setDefaultConnection('mysql');
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
     * Instantiate the export class with appropriate constructor parameters
     */
    private function instantiateExportClass()
    {
        // Handle PersonalQuotesExport which needs quoteType in constructor
        if ($this->exportClass === 'App\\Exports\\PersonalQuotesExport') {
            $quoteType = $this->requestParams['quoteType'] ?? null;

            return app($this->exportClass, ['quoteType' => $quoteType]);
        }

        // Handle exportClass which needs requestParams in constructor
        $exportWithRequestParams = [
            'App\\Exports\\AmlCftReportExport',
            'App\\Exports\\Reports\\SaleSummaryReportExport',
            'App\\Exports\\Reports\\SaleDetailReportExport',
            'App\\Exports\\Reports\\EndingPoliciesReportExport',
            'App\\Exports\\Reports\\TransactionReportExport',
            'App\\Exports\\Reports\\ActivePoliciesReportExport',
            'App\\Exports\\Reports\\EndorsementReportExport',
            'App\\Exports\\Reports\\InstallmentReportExport',
            'App\\Exports\\Reports\\ConversionAsAtReportExport',
        ];

        if (in_array($this->exportClass, $exportWithRequestParams)) {
            return app($this->exportClass, ['requestParams' => $this->requestParams]);
        }

        // For other export classes, use default instantiation
        return app($this->exportClass);
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
