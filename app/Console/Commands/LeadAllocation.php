<?php

namespace App\Console\Commands;

use App\Models\LeadAllocation as LeadAllocationModel;
use App\Services\LeadAllocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpClient\Exception\TimeoutException;

class LeadAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LeadAllocation:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(LeadAllocationService $leadAllocationService)
    {
        Log::info('Lead Allocation Command Started');
        try {
            info('Lead Allocation Started');

            if ($leadAllocationService->shouldResetUserAssignmentCountAndAvailability()) {
                $leadAllocationService->setAdvisorsToUnavailable();
            }

            $leadAllocationService->updateAllocationStatusIfNeeded();

            if (! $leadAllocationService->shouldCarAllocationProceed()) {
                info('CAR Lead Allocation Job Switch is OFF');
            } else {
                info('CAR Lead Allocation Job Switch is ON and job is about to start');
                $leadAllocationService->processCarLeads();
            }
            if (! $leadAllocationService->shouldHealthAllocationProceed()) {
                info('Health Lead Allocation Job Switch is OFF');

                return;
            } else {
                info('--------- Health Lead Allocation Started ---------');
                $availableUsers = $leadAllocationService->getAvailableAdvisors();

                $availableUsersString = $availableUsers->map(function ($user) {
                    return $user->name.'|'.$user->last_allocated;
                })->implode(',');

                info('availableUsers: '.$availableUsersString);

                $unAllocatedLeads = $leadAllocationService->getHealthUnallocatedLeads();

                foreach ($unAllocatedLeads as $unAllocatedLead) {
                    if (! $unAllocatedLead->health_team_type) {
                        $leadAllocationService->assignHealthTeamBasedOnStartingPrice($unAllocatedLead);
                    }
                }

                if (count($unAllocatedLeads) > 0) {
                    $currentIteration = now();

                    info('----------------------- HEALTH LEAD ALLOCATION STARTED FOR '.$currentIteration.' -----------------------');

                    $healthTeams = ['EBP', 'RM-SPEED', 'RM-NB'];

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
                                LeadAllocationModel::where('user_id', $user->id)->update(['last_allocated' => (float) $user->last_allocated]);
                            }
                        } else {
                            info($healthTeam.' Leads count is '.count($filteredLeadsByHealthTeam).' and available users count is '.count($filteredUsersByHealthTeam));
                        }
                    }
                    info('----------------------- HEALTH LEAD ALLOCATION ENDED FOR '.$currentIteration.' -----------------------');
                } else {
                    info('No Unallocated Leads');
                }

                info('--------- Health Lead Allocation Ended ---------');

            }
        } catch (TimeoutException $e) {
            info('**************** Lead Allocation Job is timed out now at : '.now().' **************** ');
            info('message: '.$e->getMessage());
            $this->delete();
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
