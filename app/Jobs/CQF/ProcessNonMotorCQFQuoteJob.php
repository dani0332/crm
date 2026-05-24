<?php

declare(strict_types=1);

namespace App\Jobs\CQF;

use App\Enums\QuoteTypes;
use App\Services\CQF\NonMotor\NonMotorCQFRenewalExecutionService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessNonMotorCQFQuoteJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public int $quoteId,
        public string $source,
        public QuoteTypes $quoteType,
        public int $renewalsUploadLeadsId,
        public int $renewalDaysThreshold
    ) {
        $this->onQueue('default');
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(NonMotorCQFRenewalExecutionService $executionService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $executionService->processQuoteForJob(
            $this->quoteId,
            $this->source,
            $this->quoteType,
            $this->renewalsUploadLeadsId,
            $this->renewalDaysThreshold
        );
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error(self::class.' - Job failed permanently', [
            'quoteId' => $this->quoteId,
            'quoteType' => $this->quoteType->value,
            'renewalsUploadLeadsId' => $this->renewalsUploadLeadsId,
            'error' => $exception->getMessage(),
        ]);
    }
}
