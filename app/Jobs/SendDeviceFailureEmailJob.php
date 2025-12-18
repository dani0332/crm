<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DeviceFailureTypeEnum;
use App\Services\DeviceFailureEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendDeviceFailureEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $quoteId,
        private DeviceFailureTypeEnum $failureType,
        private ?string $providerCode = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DeviceFailureEmailService $service): void
    {
        $service->executeFailureEmail(
            $this->quoteId,
            $this->failureType,
            $this->providerCode,
            $this->attempts()
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        app(DeviceFailureEmailService::class)->handleJobFailure(
            $this->quoteId,
            $this->failureType,
            $exception->getMessage()
        );
    }
}
