<?php

namespace App\Jobs\Allocation;

use App\Services\TravelRenewalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AssignTravelRenewalLeadJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $quoteUUID)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(TravelRenewalService::class)->leadAllocation($this->quoteUUID);
    }
}
