<?php

namespace App\Jobs;

use App\Models\ApplicationStorage;
use App\Models\HealthQuote;
use App\Models\LeadAllocation;
use App\Models\QuoteStatus;
use App\Models\Team;
use App\Models\User;
use App\Traits\GetUserTree;
use Carbon\Carbon;
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
            $unAllocatedLeads = $this->getUnAllocatedLeads();
            Log::channel('daily')->info('Number of leads to be allocated: ' . count($unAllocatedLeads));
            foreach ($unAllocatedLeads as $unAllocatedLead) {
                $user = $this->getNextAvailableAdvisor();
                if (!empty($user->name)) {
                    Log::channel('daily')->info('Next available advisor: ' . $user->name);
                    Log::channel('daily')->info('Allocating lead with uuid ' . $unAllocatedLead->uuid . ' to ' . $user->name);
                    $unAllocatedLead->advisor_id = $user->id;
                    $unAllocatedLead->save();
                    LeadAllocation::where('user_id', $user->id)->increment('allocation_count');
                    Log::channel('daily')->info('Lead allocated to ' . $user->name);
                } else {
                    Log::channel('daily')->info('No available advisor found for ' . $unAllocatedLead->uuid);
                }
            }
            return;
        } catch (\Exception $e) {
            Log::channel('daily')->info('Lead Allocation Job Failed');
            Log::channel('daily')->info("message: " . $e->getMessage());
            if ($this->attempts() < 4) {
                $delayInSeconds = 5 * 60;
                $this->release($delayInSeconds);
            }
        }
    }

    public function getUnAllocatedLeads()
    {
        $unAllocatedLeads = [];
        $to = Carbon::now();
        $from = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_START_DATE_FOR_LEADS')->first()->value;
        Log::channel('daily')->info('to date: ' . $to . ' from date: ' . $from);
        $unAllocatedLeads = HealthQuote::join('quote_status qs', 'qs.id', '=', 'health_quotes.quote_status_id')
            ->where('qs.text', 'Qualified')
            ->where('advisor_id', null)
            ->whereBetween('created_at', [$from, $to])
            ->get();
        return $unAllocatedLeads;
    }

    public function getNextAvailableAdvisor()
    {
        Log::channel('daily')->info('getNextAvailableAdvisor -- started');
        Log::channel('daily')->info('Fetching loggedin user (manager) subordinates');
        $healthUsers = User::where('team_id', Team::where('name', 'Health')->first()->id)->pluck('id');
        Log::channel('daily')->info('Found ' . count($healthUsers) . ' sub-ordinates');
        Log::channel('daily')->info('Fetching lead allocation records for sub-ordinates');
        $leadAllocationWithUsers = LeadAllocation::with('leadAllocationUser')->where('is_available', '=', true)->whereIn('user_id', $healthUsers)->get();
        Log::channel('daily')->info('Found ' . count($leadAllocationWithUsers) . ' lead allocation records');
        $nextAvailableUser = $this->getNextAssignableUser($leadAllocationWithUsers);
        return $nextAvailableUser;
    }

    public function getNextAssignableUser($leadAllocationWithUsers)
    {
        Log::channel('daily')->info('getNextAssignableUser -- started');
        $allAssignableUsers = collect([]);
        foreach ($leadAllocationWithUsers as $leadAllocationWithUser) {
            if ($leadAllocationWithUser->allocation_count < $leadAllocationWithUser->max_capacity || $leadAllocationWithUser->max_capacity == -1) {
                Log::channel('daily')->info('Found assignable user ' . $leadAllocationWithUser->leadAllocationUser->name);
                $allAssignableUsers->push($leadAllocationWithUser);
            }
        }
        Log::channel('daily')->info('Found ' . count($allAssignableUsers) . ' assignable users');
        $allAssignableUsers = $allAssignableUsers->sortBy('last_allocated', SORT_NATURAL);
        if ($allAssignableUsers->count() > 0) {
            Log::channel('daily')->info('Returning assignable user ' . $allAssignableUsers->first()->leadAllocationUser->name);
            return $allAssignableUsers->first()->leadAllocationUser;
        }
        return new User();
    }
}
