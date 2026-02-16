<?php

declare(strict_types=1);

namespace App\Jobs\CQF;

use App\Enums\QuoteTypes;
use App\Services\CQF\NonMotor\NonMotorCQFRenewalExecutionService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessNonMotorCQFChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    /**
     * @param  array<int, int>  $quoteIds
     */
    public function __construct(
        public array $quoteIds,
        public string $source,
        public QuoteTypes $quoteType,
        public int $renewalsUploadLeadsId,
        public int $renewalDaysThreshold
    ) {}

    public function handle(NonMotorCQFRenewalExecutionService $executionService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $executionService->processChunkForJob(
            $this->quoteIds,
            $this->source,
            $this->quoteType,
            $this->renewalsUploadLeadsId,
            $this->renewalDaysThreshold
        );
    }
}
