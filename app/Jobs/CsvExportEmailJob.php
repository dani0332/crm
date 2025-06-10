<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\CsvExportableInterface;
use App\Services\EmailExportService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class CsvExportEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes
    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(
        private CsvExportableInterface $exporter,
        private string $recipientEmail,
        private string $subject,
        private array $requestParams = [],
        private array $ccRecipients = []
    ) {
        $this->onQueue('renewals');
    }

    /**
     * Execute the job
     */
    public function handle(EmailExportService $emailExportService): void
    {
        $startTime = microtime(true);
        $initialMemory = memory_get_usage(true) / 1024 / 1024;
        $exporterClass = get_class($this->exporter);
        $fileName = $this->requestParams['fileName'] ?? 'export';

        LoggerService::info('CSV export job started', [
            'exporter' => $exporterClass,
            'fileName' => $fileName,
            'memory' => round($initialMemory, 2).'MB',
            'attempt' => $this->attempts(),
        ]);

        try {
            $emailExportService->sendCsvByEmail(
                $this->exporter,
                $this->recipientEmail,
                $this->subject,
                $this->requestParams,
                $this->ccRecipients
            );

            $this->logSuccess($startTime, $fileName, $exporterClass);

        } catch (Throwable $e) {
            $this->logError($e, $startTime, $fileName, $exporterClass);
            throw $e;
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(Throwable $exception): void
    {
        $fileName = $this->requestParams['fileName'] ?? 'export';
        $exporterClass = get_class($this->exporter);

        LoggerService::error('CSV export job permanently failed', [
            'exporter' => $exporterClass,
            'fileName' => $fileName,
            'recipient' => $this->recipientEmail,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the middleware the job should pass through
     */
    public function middleware(): array
    {
        $lockKey = md5(
            get_class($this->exporter).
            $this->recipientEmail.
            ($this->requestParams['fileName'] ?? 'export')
        );

        return [
            (new WithoutOverlapping($lockKey))
                ->dontRelease()
                ->expireAfter(300), // 5 minutes lock expiration
        ];
    }

    /**
     * Log successful completion
     */
    private function logSuccess(float $startTime, string $fileName, string $exporterClass): void
    {
        $executionTime = round(microtime(true) - $startTime, 2);
        $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        LoggerService::info('CSV export job completed successfully', [
            'exporter' => $exporterClass,
            'fileName' => $fileName,
            'executionTime' => $executionTime.'s',
            'peakMemory' => $peakMemory.'MB',
        ]);
    }

    /**
     * Log error with context
     */
    private function logError(Throwable $e, float $startTime, string $fileName, string $exporterClass): void
    {
        $executionTime = round(microtime(true) - $startTime, 2);
        $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $jobId = $this->job?->getJobId() ?? 'unknown';

        LoggerService::error('CSV export job failed', [
            'jobId' => $jobId,
            'exporter' => $exporterClass,
            'fileName' => $fileName,
            'executionTime' => $executionTime.'s',
            'peakMemory' => $peakMemory.'MB',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())
                ->filter(fn ($trace) => isset($trace['file']) && str_contains($trace['file'], '/app'))
                ->all(),
        ]);
    }

    /**
     * Clean up resources
     */
    private function cleanup(): void
    {
        DB::setDefaultConnection('mysql');
        Auth::logout();
        gc_collect_cycles();
    }
}
