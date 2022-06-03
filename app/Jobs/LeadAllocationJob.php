<?php

namespace App\Jobs;

use App\Services\LeadAllocationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Facades\Log;

class LeadAllocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;
    protected $leadAllocationService;

    public $maxTries = 5;
    public $timeout = 300;
    public $backoff = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(LeadAllocationService $leadAllocationService)
    {
        $this->leadAllocationService = $leadAllocationService;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $unAllocatedLeads = $this->leadAllocationService->getUnAllocatedLeads();
            foreach ($unAllocatedLeads as $unAllocatedLead) {
                $this->leadAllocationService->allocateLead($unAllocatedLead);
            }
        } catch (\Exception $e) {
            Log::channel('daily')->info("message: " . $e->getMessage());
            if ($this->attempts() < 4) {
                $delayInSeconds = 5 * 60;
                $this->release($delayInSeconds);
            }
        }
    }
}
