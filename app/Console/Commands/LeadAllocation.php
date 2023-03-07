<?php

namespace App\Console\Commands;

use App\Jobs\LeadAllocationJob;
use App\Services\LeadAllocationService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
        // Log::info('Lead Allocation Command Started');
        // dispatch(new LeadAllocationJob());

        try {
            info('Lead Allocation Command Started');

            if ($leadAllocationService->shouldResetUserAssignmentCountAndAvailability()) {
                $leadAllocationService->setAdvisorsToUnavailable();
            }

            $leadAllocationService->updateAllocationStatusIfNeeded();

            if ($leadAllocationService->shouldCarAllocationProceed()) {
                info('CAR Lead Allocation Job Switch is ON and job is about to start');
                $leadAllocationService->processCarLeads();
            } else {
                info('CAR Lead Allocation Job Switch is OFF');
            }
            if (! $leadAllocationService->leadAllocationSwitchStatus()) {
                info('Health Lead Allocation Job Switch is OFF');

                return;
            } else {
                $availableUsers = $leadAllocationService->getAvailableAdvisors();

                $availableUsersString = '';

                $availableUsers->each(function ($user) use (&$availableUsersString) {
                    $availableUsersString .= $user->name.'|'.$user->last_allocated.',';
                });

                info('availableUsers: '.$availableUsersString);

                $unAllocatedLeads = $leadAllocationService->getUnAllocatedLeads();

                if (count($unAllocatedLeads) > 0) {
                    $currentIteration = now();

                    info('----------------------- HEALTH LEAD ALLOCATION STARTED AT '.$currentIteration.' -----------------------');

                    $healthTeams = ['EBP', 'RM-Speed', 'RM-NB'];

                    foreach ($healthTeams as $healthTeam) {
                        info('Health Lead Allocation Started for health team: '.$healthTeam);

                        $filteredLeadsByHealthTeam = $unAllocatedLeads->filter(function ($lead) use ($healthTeam) {
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

                                $leadAllocationService->assignLead($lead, $advisor->id, false);

                                info('-------> Health Lead Allocation Done for lead: '.$lead->uuid.' and advisor: '.$advisor->name);

                                $filteredUsersByHealthTeam->each(function ($user) use ($advisor) {
                                    if ($user->id == $advisor->id) {
                                        $user->last_allocated = microtime(true);
                                    }
                                });
                                info('----------------------- HEALTH LEAD ALLOCATION ENDED FOR LEAD '.$lead->uuid.' -----------------------');
                            }

                            foreach ($filteredUsersByHealthTeam as $user) {
                                self::where('user_id', $user->id)->update(['last_allocated' => (float) $user->last_allocated]);
                            }
                        } else {
                            info($healthTeam.' Leads count is '.$filteredLeadsByHealthTeam->count().' and available users count is '.$filteredUsersByHealthTeam->count());
                        }
                    }
                    info('----------------------- HEALTH LEAD ALLOCATION ENDED AT '.$currentIteration.' -----------------------');
                } else {
                    info('No Unallocated Leads');
                }

                return;
            }
        } catch (Exception $e) {
            info('Lead Allocation Job Timed Out now at : '.now());

            info('Lead Allocation Job Timed Out Message: '.$e->getMessage());

            return;
        }
    }
}
