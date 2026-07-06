<?php

namespace App\Jobs;

use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use App\Services\PqaAllocation\PqaAllocationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchPqaAllocationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    public function __construct(public string $quoteUuid, public QuoteTypes $quoteType) {}

    public function uniqueId(): string
    {
        return "{$this->quoteType->value}:{$this->quoteUuid}";
    }

    public function handle(): void
    {
        app(PqaAllocationService::class)->executeAllocation($this->quoteUuid, false, $this->quoteType);
    }

    public function failed(\Throwable $e): void
    {
        LoggerService::error(self::class.'::failed - PQA allocation job exhausted retries', [
            'quote_uuid' => $this->quoteUuid,
            'quote_type' => $this->quoteType->value,
        ], $e);
    }
}
