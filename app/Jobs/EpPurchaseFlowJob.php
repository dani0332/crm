<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTO\EpBookingContext;
use App\Services\EpExcessCashbackService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Sleep;
use Throwable;

class EpPurchaseFlowJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 300;
    public $backoff = 180;

    private string $logPrefix = 'EpPurchaseFlow - Job:';
    private array $logExtra = [];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public EpBookingContext $context
    ) {
        $this->logExtra = [
            'etId' => $this->context->etId,
            'quoteId' => $this->context->quoteId,
            'quoteTypeId' => $this->context->quoteTypeId,
            'quoteUUID' => $this->context->quoteUUID,
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LoggerService::info("{$this->logPrefix} Starting", extra: $this->logExtra);

        $epEcbService = app(EpExcessCashbackService::class, ['context' => $this->context]);
        $epEcbService->init();

        // Execute purchase flow steps (Token, Quote, Policy creation)
        $epEcbService->executeSteps();

        // Wait 2 minute before processing documents
        Sleep::for(2)->minutes();

        // Sync policy documents
        $epEcbService->syncPolicyDocuments();

        $epEcbService->handleJobSuccess();

        LoggerService::info("{$this->logPrefix} Completed", extra: $this->logExtra);
    }

    /**
     * Handle a job failure (called after all retry attempts are exhausted)
     */
    public function failed(Throwable $exception): void
    {
        LoggerService::error("{$this->logPrefix} Failed after all retries", extra: [
            ...$this->logExtra,
            'error' => $exception->getMessage()
        ]);

    }

    /**
     * Determine if job should retry based on exception
     */
    public function shouldRetry(Throwable $exception): bool
    {
        // Retry for network/timeout issues and document download issues
        if ($exception instanceof \Illuminate\Http\Client\ConnectionException ||
            $exception instanceof \Illuminate\Http\Client\RequestException ||
            str_contains($exception->getMessage(), 'timeout') ||
            str_contains($exception->getMessage(), 'connection') ||
            str_contains($exception->getMessage(), 'download') ||
            str_contains($exception->getMessage(), 'server error')) {
            return true;
        }

        // Don't retry for business logic errors
        return false;
    }
}
