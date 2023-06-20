<?php

namespace App\Jobs;

use App\Mail\LeadAllocationFailedNotification as MailLeadAllocationFailedNotification;
use App\Models\LeadAllocation;
use App\Services\LeadAllocationService;
use App\Traits\GetUserTreeTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpClient\Exception\TimeoutException as ExceptionTimeoutException;
use Throwable;

//Scheduled to delete, job moved to console command
class CarLeadAllocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, GetUserTreeTrait;

    public $tries = 2;
    public $timeout = 55;
    public $backoff = 20;
    private $leadAllocationJobId = 'lead_allocation';

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
    public function handle(LeadAllocationService $leadAllocationService)
    {
        try {
            info('Lead Allocation Job Started');

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

                $unAllocatedLeads = $leadAllocationService->getHealthUnallocatedLeads();

                if (count($unAllocatedLeads) > 0) {
                    $currentIteration = now();

                    info('----------------------- HEALTH LEAD ALLOCATION STARTED FOR  '.$currentIteration.' -----------------------');

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
                } else {
                    info('No Unallocated Leads');
                }

                return;
            }
        } catch (ExceptionTimeoutException $e) {
            info('**************** Lead Allocation Job is timed out now at : '.now().' **************** ');

            info('message: '.$e->getMessage());
            $this->delete();
        }
    }

    public function failed(Throwable $exception)
    {
        if ($exception) {
            Log::error('Exception in lead allocation : '.$exception->getMessage());
            Mail::send(new MailLeadAllocationFailedNotification($exception));
        }
    }

    public function middleware()
    {
        return [(new WithoutOverlapping('lead_allocation'))->dontRelease()];
    }
}
