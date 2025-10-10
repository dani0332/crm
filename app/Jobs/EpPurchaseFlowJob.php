<?php

namespace App\Jobs;

use App\DTO\EpBookingContext;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Mail\EpFailureNotification;
use App\Services\EpEcbService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EpPurchaseFlowJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 300;
    public $backoff = 180;
    private string $logPrefix = 'EpPurchaseFlow - Job:';
    private array $logExtra = [];
    public mixed $quote = null;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public EpBookingContext $context
    ) {
        $this->logExtra = $this->context->logExtra;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Start feature and quote logging
        LoggerService::startQuoteLogging($this->context->quoteCode, LoggerFeatureEnum::EP_PROCESS_PURCHASE_FLOW);

        LoggerService::info("{$this->logPrefix} Starting", extra: $this->logExtra);

        $epEcbService = new EpEcbService($this->context);
        $epEcbService->init();

        // Execute purchase flow steps (Token, Quote, Policy creation)
        $epEcbService->executeSteps(); // STATUS_PAYMENT_SUCCEED
    }

    /**
     * Handle a job failure (called after all retry attempts are exhausted)
     */
    public function failed(Throwable $exception): void
    {
        LoggerService::error("{$this->logPrefix} Failed after all retries", extra: [
            ...$this->logExtra,
            'error' => $exception->getMessage(),
        ]);

        try {
            Mail::send(new EpFailureNotification($this->context->quoteId, $this->context->quoteTypeId, $this->context->etId));
            LoggerService::info("{$this->logPrefix} Embedded Product failure email sent successfully");
        } catch (Throwable $e) {
            LoggerService::error("{$this->logPrefix} Failed to send Embedded Product failure email: ".$e->getMessage());
        }
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
            str_contains($exception->getMessage(), 'server error')
        ) {
            return true;
        }

        // Don't retry for business logic errors
        return false;
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        $lockKey = "ep-purchase-flow-{$this->context->etId}-{$this->context->quoteCode}";

        return [
            (new WithoutOverlapping($lockKey))
                ->dontRelease()
                ->expireAfter(300),
        ];
    }
}
