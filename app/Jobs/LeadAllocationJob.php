<?php

namespace App\Jobs;

use App\Models\ApplicationStorage;
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

            $leadAllocationJobSwitch = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_JOB_SWITCH')->first();
            Log::info('Lead Allocation Job Switch: ' . $leadAllocationJobSwitch->value);
            if ($leadAllocationJobSwitch->value == '0') {
                Log::info('Lead Allocation Job Switch is OFF');
                return;
            }

            $this->leadAllocationService->setAdvisorsToUnavailable();

            $unAllocatedLeads = $this->leadAllocationService->getUnAllocatedLeads();
            Log::info('Number of leads to be allocated: ' . count($unAllocatedLeads));

            foreach ($unAllocatedLeads as $unAllocatedLead) {
                $user = $this->leadAllocationService->getNextAvailableAdvisor();

                if (!empty($user->name)) {

                    $this->leadAllocationService->getHealthUserSubTeamName($user);

                    $subTeamName = $this->leadAllocationService->getHealthUserSubTeamName($user);
                    Log::info('Lead Health Team Type ' . $unAllocatedLead->health_team_type . ' User Sub Team Name: ' . $subTeamName);

                    if ($subTeamName == $unAllocatedLead->health_team_type) {
                        Log::info('Next available advisor: ' . $user->name);
                        Log::info('Allocating lead with uuid ' . $unAllocatedLead->uuid . ' to ' . $user->name);

                        $this->leadAllocationService->assignLead($unAllocatedLead, $user);
                        Log::info('Lead allocated to ' . $user->name);
                    } else {
                        Log::info('Skipping allocation of lead with uuid ' . $unAllocatedLead->uuid . ' to ' . $user->name . ' as the user is not in the correct sub team');
                    }
                } else {
                    Log::info('No available advisor found for ' . $unAllocatedLead->uuid);
                }
            }
            return;
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
