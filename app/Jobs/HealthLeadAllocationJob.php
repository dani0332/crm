<?php

namespace App\Jobs;

use App\Models\LeadAllocation;
use App\Services\LeadAllocationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HealthLeadAllocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 55;
    public $backoff = 20;
    private $leadAllocationJobId = 'health_lead_allocation';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->onQueue('lms');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(LeadAllocationService $leadAllocationService)
    {
        if (! $leadAllocationService->leadAllocationSwitchStatus()) {
            info('Health Lead Allocation Job Switch is OFF');

        } else {
            info('--------- Health Lead Allocation Started ---------');
            $availableUsers = $leadAllocationService->getAvailableAdvisors();

            $availableUsersString = $availableUsers->map(function ($user) {
                return $user->name.'|'.$user->last_allocated;
            })->implode(',');

            info('availableUsers: '.$availableUsersString);

            $unAllocatedLeads = $leadAllocationService->getHealthUnallocatedLeads();

            foreach ($unAllocatedLeads as $unAllocatedLead) {
                $leadAllocationService->assignHealthTeamBasedOnStartingPrice($unAllocatedLead);
            }

            if (count($unAllocatedLeads) > 0) {
                $currentIteration = now();

                info('----------------------- HEALTH LEAD ALLOCATION STARTED FOR '.$currentIteration.' -----------------------');

                $healthTeams = ['EBP', 'RM-Speed', 'RM-NB'];

                foreach ($healthTeams as $healthTeam) {
                    info('Health Lead Allocation Started for health team: '.$healthTeam);

                    $filteredLeadsByHealthTeam = $this->getFilteredLeadsByHealthTeam($unAllocatedLeads, $healthTeam);
                    $filteredUsersByHealthTeam = $this->getUsersByHealthTeam($availableUsers, $healthTeam);

                    if (count($filteredLeadsByHealthTeam) > 0 && count($filteredUsersByHealthTeam) > 0) {

                        foreach ($filteredLeadsByHealthTeam as $lead) {
                            info('----------------------- HEALTH LEAD ALLOCATION STARTED FOR LEAD '.$lead->uuid.' -----------------------');

                            [$filteredUsersByHealthTeam, $advisor] = $this->sortUsersByLastAllocatedTime($filteredUsersByHealthTeam, $leadAllocationService, $lead);
                            info('-------> Health Lead Allocation Done for lead: '.$lead->uuid.' and advisor: '.$advisor->name);

                            $this->updateLastAllocatedForAdvisor($filteredUsersByHealthTeam, $advisor);
                            sleep(1);
                            info('----------------------- HEALTH LEAD ALLOCATION ENDED FOR LEAD '.$lead->uuid.' -----------------------');
                        }
                        foreach ($filteredUsersByHealthTeam as $user) {
                            LeadAllocation::where('user_id', $user->id)->update(['last_allocated' => (float) $user->last_allocated]);
                        }
                    } else {
                        info($healthTeam.' Leads count is'.count($filteredLeadsByHealthTeam).' and available users count is '.count($filteredUsersByHealthTeam));
                    }
                }
                info('----------------------- HEALTH LEAD ALLOCATION ENDED FOR '.$currentIteration.' -----------------------');
            } else {
                info('No Unallocated Leads');
            }

            info('--------- Health Lead Allocation Ended ---------');

        }

    }

    public function getFilteredLeadsByHealthTeam($unAllocatedLeads, string $healthTeam): mixed
    {
        return $unAllocatedLeads->filter(function ($lead) use ($healthTeam) {
            return strtolower($lead->health_team_type) == strtolower($healthTeam) ? $lead : false;
        });
    }

    public function getUsersByHealthTeam($availableUsers, string $healthTeam): mixed
    {
        return $availableUsers->filter(function ($user) use ($healthTeam) {
            return strtolower($user->sub_team_name) == strtolower($healthTeam) ? $user : false;
        });
    }

    public function updateLastAllocatedForAdvisor($filteredUsersByHealthTeam, $advisor): void
    {
        // Update last_allocated time for the user
        $filteredUsersByHealthTeam->each(function ($user) use ($advisor) {
            if ($user->id == $advisor->id) {
                $user->last_allocated = microtime(true);
            }
        });
    }

    public function sortUsersByLastAllocatedTime(mixed $filteredUsersByHealthTeam, LeadAllocationService $leadAllocationService, $lead): array
    {
        // Sort users by last_allocated time
        $filteredUsersByHealthTeam = $filteredUsersByHealthTeam->sortBy('last_allocated', SORT_NATURAL)->flatten();

        // Get the first available user
        $advisor = $filteredUsersByHealthTeam->first();

        // Assign lead to the user
        $leadAllocationService->assignLead($lead, $advisor->id, false);

        return [$filteredUsersByHealthTeam, $advisor];
    }
}
