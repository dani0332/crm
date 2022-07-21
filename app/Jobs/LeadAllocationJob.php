<?php

namespace App\Jobs;

use App\Models\LeadAllocation;
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

    public $tries = 3;
    public $timeout = 30;
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
                info('Lead Allocation Job Switch is OFF');
                return;
            }
            else
            {
                $this->leadAllocationService->setAdvisorsToUnavailable();
                $unAllocatedLeads = $this->leadAllocationService->getUnAllocatedLeads();
                if(count($unAllocatedLeads) > 0)
                {
                    $availableUsers = $this->leadAllocationService->getAvailableAdvisors();
                    $healthTeams = ['EBP', 'RM-Speed', 'RM-NB'];
                    $availableUsersString = '';
                    $availableUsers->each(function ($user) use (&$availableUsersString) {
                        $availableUsersString .= $user->name . '|'. $user->last_allocated. ',' ;
                    });
                    info('availableUsers: ' . $availableUsersString);
                    foreach($healthTeams as $healthTeam)
                    {
                        info('Lead Allocation Started for health team: ' . $healthTeam);
                        $filteredLeadsByHealthTeam = $unAllocatedLeads->filter(function ($lead) use ($healthTeam) {
                            return strtolower($lead->health_team_type) == strtolower($healthTeam) ? $lead : false;
                        });
                        $filteredUsersByHealthTeam = $availableUsers->filter(function ($user) use ($healthTeam) {
                            return strtolower($user->sub_team_name) == strtolower($healthTeam) ? $user : false;
                        });

                        if($filteredLeadsByHealthTeam->count() > 0 && $filteredUsersByHealthTeam->count() > 0)
                        {
                            foreach($filteredLeadsByHealthTeam as $lead)
                            {
                                $filteredUsersByHealthTeam = $filteredUsersByHealthTeam->sortBy('last_allocated', SORT_NATURAL)->flatten();
                                $advisor = $filteredUsersByHealthTeam->first();
                                $this->leadAllocationService->assignLead($lead, $advisor->id , false);
                                info('Lead Allocation Done for lead: ' . $lead->uuid . ' and advisor: ' . $advisor->name);
                                $filteredUsersByHealthTeam->each(function ($user) use ($advisor) {
                                    if($user->id == $advisor->id)
                                    {
                                        $user->last_allocated = microtime(true);
                                    }
                                });
                            };

                            foreach($filteredUsersByHealthTeam as $user)
                            {
                                LeadAllocation::where('user_id', $user->id)->update(['last_allocated' => (float)$user->last_allocated]);
                            }
                        }
                        else
                        {
                            info($healthTeam . ' Leads count is '. $filteredLeadsByHealthTeam->count() . ' and available users count is ' . $filteredUsersByHealthTeam->count());
                            info('No leads or users available for health team: ' . $healthTeam);
                        }
                    }
                }
                else
                {
                    info('No Unallocated Leads');
                }
                return;
            }
        } catch (\Exception $e) {
            info('Lead Allocation Job Failed');
            info("message: " . $e->getMessage());
            if ($this->attempts() < 4) {
                $delayInSeconds = 2 * 60;
                $this->release($delayInSeconds);
            }
        }
    }
}
