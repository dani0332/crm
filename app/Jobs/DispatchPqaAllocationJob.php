<?php

namespace App\Jobs;

use App\Enums\QuoteTypes;
use App\Services\PqaAllocation\PqaAllocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchPqaAllocationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $quoteUuid, public QuoteTypes $quoteType) {}

    public function handle(): void
    {
        app(PqaAllocationService::class)->executeAllocation($this->quoteUuid, false, $this->quoteType);
    }
}
