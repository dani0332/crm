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
            Log::channel('daily')->info('Lead Allocation Job Started');

            $leadAllocationJobSwitch = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_JOB_SWITCH')->first();
            Log::channel('daily')->info('Lead Allocation Job Switch: ' . $leadAllocationJobSwitch->value);
            if ($leadAllocationJobSwitch->value == '0') {
                Log::channel('daily')->info('Lead Allocation Job Switch is OFF');
                return;
            }

            $this->leadAllocationService->setAdvisorsToUnavailable();

            $unAllocatedLeads = $this->leadAllocationService->getUnAllocatedLeads();
            Log::channel('daily')->info('Number of leads to be allocated: ' . count($unAllocatedLeads));

            foreach ($unAllocatedLeads as $unAllocatedLead) {
                $user = $this->leadAllocationService->getNextAvailableAdvisor();

                if (!empty($user->name)) {

                    $this->leadAllocationService->getHealthUserSubTeamName($user);

                    $subTeamName = $this->leadAllocationService->getHealthUserSubTeamName($user);
                    Log::channel('daily')->info('Lead Health Team Type ' . $unAllocatedLead->health_team_type . ' User Sub Team Name: ' . $subTeamName);

                    if ($subTeamName == $unAllocatedLead->health_team_type) {
                        Log::channel('daily')->info('Next available advisor: ' . $user->name);
                        Log::channel('daily')->info('Allocating lead with uuid ' . $unAllocatedLead->uuid . ' to ' . $user->name);

                        $this->leadAllocationService->assignLead($unAllocatedLead, $user);
                        Log::channel('daily')->info('Lead allocated to ' . $user->name);
                    } else {
                        Log::channel('daily')->info('Skipping allocation of lead with uuid ' . $unAllocatedLead->uuid . ' to ' . $user->name . ' as the user is not in the correct sub team');
                    }
                } else {
                    Log::channel('daily')->info('No available advisor found for ' . $unAllocatedLead->uuid);
                }
            }
            return;
        } catch (\Exception $e) {
            Log::channel('daily')->info('Lead Allocation Job Failed');
            Log::channel('daily')->info("message: " . $e->getMessage());
            if ($this->attempts() < 4) {
                $delayInSeconds = 2 * 60;
                $this->release($delayInSeconds);
            }
        }
    }
}
