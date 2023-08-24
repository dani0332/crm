<?php

namespace App\Jobs;

use App\Enums\AssignmentTypeEnum;
use App\Models\LeadAllocation;
use App\Services\HealthAllocationService;

class HealthAllocationJob extends LeadAllocationJobInterface
{
    protected HealthAllocationService $healthAllocationService;
    protected int $quoteId;

    public function __construct(HealthAllocationService $healthAllocationService, $quoteId)
    {
        $this->healthAllocationService = $healthAllocationService;
        $this->quoteId = $quoteId;
    }

    public function handle()
    {
        // Fetch the leads to process, including deferred leads if needed
        $leads = $this->fetchLeads();

        if (count($leads) > 0) {

            $availableUsers = $this->findAvailableUsers(0);

            $currentIteration = now();

            info('----------------------- HEALTH LEAD ALLOCATION STARTED FOR  '.$currentIteration.' -----------------------');

            $healthTeams = ['EBP', 'RM-Speed', 'RM-NB'];

            foreach ($healthTeams as $healthTeam) {
                info('Health Lead Allocation Started for health team: '.$healthTeam);

                $filteredLeadsByHealthTeam = $leads->filter(function ($lead) use ($healthTeam) {
                    return strtolower($lead->health_team_type) == strtolower($healthTeam) ? $lead : false;
                });

                $filteredUsersByHealthTeam = $availableUsers->filter(function ($user) use ($healthTeam) {
                    return strtolower($user->sub_team_name) == strtolower($healthTeam) ? $user : false;
                });

                if ($filteredLeadsByHealthTeam->count() > 0 && $filteredUsersByHealthTeam->count() > 0) {
                    foreach ($filteredLeadsByHealthTeam as $lead) {
                        info('----------------------- HEALTH LEAD ALLOCATION STARTED FOR LEAD '.$lead->uuid.' -----------------------');

                        $filteredUsersByHealthTeam = $filteredUsersByHealthTeam->sortBy('last_allocated', SORT_NATURAL)->flatten();

                        $advisor = $filteredUsersByHealthTeam->first();

                        $this->assignLead($lead, $advisor->id);

                        info('-------> Health Lead Allocation Done for lead: '.$lead->uuid.' and advisor: '.$advisor->name);

                        $filteredUsersByHealthTeam->each(function ($user) use ($advisor) {
                            if ($user->id == $advisor->id) {
                                $user->last_allocated = microtime(true);
                            }
                        });
                        sleep(1);
                        info('----------------------- HEALTH LEAD ALLOCATION ENDED FOR LEAD '.$lead->uuid.' -----------------------');
                    }

                    foreach ($filteredUsersByHealthTeam as $user) {
                        LeadAllocation::where('user_id', $user->id)->update(['last_allocated' => (float) $user->last_allocated]);
                    }
                } else {
                    info($healthTeam.' Leads count is '.$filteredLeadsByHealthTeam->count().' and available users count is '.$filteredUsersByHealthTeam->count());
                }
            }
            info('----------------------- HEALTH LEAD ALLOCATION ENDED FOR  '.$currentIteration.' -----------------------');
        }
    }

    protected function fetchLeads(): mixed
    {
        return $this->healthAllocationService->fetchLeads($this->quoteId);
    }

    protected function findAvailableUsers($tierId)
    {
        return $this->healthAllocationService->getEligibleUsersForAllocation();
    }

    protected function assignLead($lead, $userId, $tier = null): void
    {
        $this->healthAllocationService->processAssignment($lead, $userId, AssignmentTypeEnum::SYSTEM_ASSIGNED);
    }

    protected function findRules($lead)
    {

    }

    protected function finalizeAdvisors($lead, $tier, $users, $rules)
    {

    }

    protected function findTier($lead)
    {

    }
}
