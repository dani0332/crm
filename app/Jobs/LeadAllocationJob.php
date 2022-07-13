<?php

namespace App\Jobs;

use App\Models\ApplicationStorage;
use App\Models\User;
use App\Services\LeadAllocationService;
use App\Traits\GetUserTree;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LeadAllocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, GetUserTree, SerializesModels;

    public $maxTries = 5;
    public $timeout = 300;
    public $backoff = 3;
    public $leadAllocationService;

    public function __construct(LeadAllocationService $leadAllocationService)
    {
        $this->leadAllocationService = $leadAllocationService;
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('Lead Allocation Job Started');
            if (!$this->leadAllocationService->leadAllocationSwitchStatus()) {
                Log::info('Lead Allocation Job Switch is OFF');
                return;
            }
            else
            {
                $this->leadAllocationService->setAdvisorsToUnavailable();
                $unAllocatedLeads = $this->leadAllocationService->getUnAllocatedLeads();
                Log::info('Number of leads to be allocated: ' . count($unAllocatedLeads));
                foreach ($unAllocatedLeads as $unAllocatedLead) {
                    Log::info('Allocation criteria checking for lead :  ' . $unAllocatedLead->uuid);
                    dispatch(new LeadAssignmentJob($unAllocatedLead, $this->leadAllocationService));
                }
                return;
            }
        } catch (\Exception $e) {
            Log::info('Lead Allocation Job Failed');
            Log::info("message: " . $e->getMessage());
            if ($this->attempts() < 4) {
                $delayInSeconds = 2 * 60;
                $this->release($delayInSeconds);
            }
        }
    }
}
