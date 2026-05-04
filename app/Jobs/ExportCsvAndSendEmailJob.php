<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Jobs\Middleware\FreshRequest;
use App\Models\User;
use App\Services\ClaimsService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExportCsvAndSendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 300 (5 minutes) 900 (15 minutes)
    public $tries = 2;
    public $backoff = 120;
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

        $this->onQueue('ocr_dedicated');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::CSV_EXPORT);

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

            // Login on the write connection before CsvExportService::generateCsvFileWithCount switches to mysql_read.
            // has already been downgraded to the read replica, causing a read-only error.
            if (! Auth::check()) {
                if (! empty($this->requestParams['user'])) {
                    Auth::login($this->requestParams['user']);
                } else {
                    LoggerService::warning('No user provided for logged in context.');
                }

                request()->merge($this->requestParams);
                LoggerService::info('Request parameters after merge (excluding user):', [
                    'request_params' => Arr::except(request()->all(), ['user']),
                ]);
            }

            // Process CSV and send email (optional ccRecipients in requestParams; see EmailExportService).
            // Only CC addresses that belong to active users in the User model (ignore arbitrary client-supplied emails).
            $ccRecipients = $this->requestParams['ccRecipients'] ?? [];
            if (! is_array($ccRecipients)) {
                $ccRecipients = array_filter(array_map('trim', explode(',', (string) $ccRecipients)));
            } else {
                $ccRecipients = array_values(array_filter(array_map('trim', $ccRecipients)));
            }

            $ccRecipients = $this->filterCcRecipientsToActiveUserEmails($ccRecipients);

            $exportInstance->sendEmailWithCSVAttachment(
                $this->requestParams['recipientEmail'],
                $this->requestParams['subject'],
                $this->requestParams,
                $ccRecipients,
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
            if (Auth::check()) {
                Auth::logout();
            }
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
     * Keep only CC addresses that match an active {@see User} record (case-insensitive email match).
     *
     * @param  array<int, string>  $rawEmails
     * @return array<int, string>
     */
    private function filterCcRecipientsToActiveUserEmails(array $rawEmails): array
    {
        $trimmed = [];
        foreach ($rawEmails as $email) {
            $normalized = trim((string) $email);
            if ($normalized !== '') {
                $trimmed[] = $normalized;
            }
        }

        $trimmed = array_values(array_unique($trimmed));

        if ($trimmed === []) {
            return [];
        }

        $activeUsers = User::query()
            ->activeUser()
            ->whereIn('email', $trimmed)
            ->get(['email']);

        $canonicalByLower = [];
        foreach ($activeUsers as $user) {
            $canonicalByLower[strtolower($user->email)] = $user->email;
        }

        $resolved = [];
        foreach ($trimmed as $email) {
            $lower = strtolower($email);
            if (isset($canonicalByLower[$lower])) {
                $resolved[$lower] = $canonicalByLower[$lower];
            }
        }

        $resolvedList = array_values($resolved);

        $requestedLower = array_map(strtolower(...), $trimmed);
        $resolvedLower = array_map(strtolower(...), $resolvedList);
        $ignoredLower = array_values(array_diff($requestedLower, $resolvedLower));

        if ($ignoredLower !== []) {
            LoggerService::warning('CSV export CC recipients ignored: no matching active user', [
                'feature' => LoggerFeatureEnum::CSV_EXPORT->value,
                'ignored_emails' => $ignoredLower,
            ]);
        }

        return $resolvedList;
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
            'App\\Exports\\Reports\\ConversionOptimizationReportExport',
            'App\\Exports\\ClaimsExport',
        ];

        if (in_array($this->exportClass, $exportWithRequestParams)) {
            // Special handling for ClaimsExport which needs ClaimsService as first parameter
            if ($this->exportClass === 'App\\Exports\\ClaimsExport') {
                return app($this->exportClass, [
                    'claimsService' => app(ClaimsService::class),
                    'requestParams' => $this->requestParams,
                ]);
            }

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
            new FreshRequest,
            (new WithoutOverlapping($lockKey))
                ->dontRelease()
                ->expireAfter($this->timeout),
        ];
    }
}
