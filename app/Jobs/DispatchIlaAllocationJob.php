<?php

namespace App\Jobs;

use App\Enums\QuoteTypes;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchIlaAllocationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $quoteUuid, public QuoteTypes $quoteType) {}

    public function handle(): void
    {
        $this->quoteType->allocate(uuid: $this->quoteUuid, overrideAdvisorId: false);
    }
}
