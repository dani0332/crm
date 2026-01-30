<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\Logger\LoggerService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessPaymentStatusUpdateJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 180;
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $quoteType,
        private readonly string $quoteId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::PAYMENT_STATUS_UPDATE, $this->quoteId);

        // Call the service method directly - JsonResponse return value is ignored in job context
        $notificationService->paymentStatusUpdate($this->quoteType, $this->quoteId);
    }

    public function uniqueId(): string
    {
        return 'payment-status-update-job-'.$this->quoteId;
    }

    public function failed(\Throwable $exception): void
    {
        LoggerService::error("Payment Status Update Job failed for: {$this->quoteId} ", exception: $exception);
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->quoteId))->dontRelease()];
    }
}
