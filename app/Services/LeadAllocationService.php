<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Models\ApplicationStorage;
use App\Models\HealthQuote;
use App\Models\LeadAllocation;
use App\Models\Team;
use App\Models\User;
use App\Traits\GetUserTree;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;
use Auth;
use Illuminate\Support\Facades\Log;

class LeadAllocationService extends BaseService
{
    use GetUserTree;


    public function getGridData(Request $request)
    {
        $userAgainstManagerWithDetail = DB::table('lead_allocation as la')
            ->select('la.id as id', 'la.user_id as userId', 'la.allocation_count', 'la.max_capacity', 'la.is_available', 'la.last_allocated', 't.name as teamName', 'u.name as userName')
            ->join('users as u', 'la.user_id', '=', 'u.id')
            ->leftjoin('teams as t', 'u.team_id', '=', 't.id')
            ->where('u.manager_id', '=', $request->user()->id)
            ->where(strtolower('t.name'), '=', strtolower(quoteTypeCode::Health))
            ->get();
        return $userAgainstManagerWithDetail;
    }

    public function createLeadAllocationRecord($userId)
    {
        $leadAllocation = new LeadAllocation();
        $leadAllocation->user_id = $userId;
        $leadAllocation->allocation_count = 0;
        $leadAllocation->last_allocation_date = Carbon::now()->timestamp;
        $leadAllocation->max_capacity = 0;
        $leadAllocation->is_available = false;
        $leadAllocation->save();
    }

    public function updateUserAllocationRecord($userId, $allocationCount, $maxCapacity, $isAvailable)
    {
        $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
        if (isset($allocationCount)) $leadAllocation->allocation_count = $allocationCount;
        if (isset($max_capacity)) $leadAllocation->max_capacity = $maxCapacity;
        if (isset($isAvailable)) $leadAllocation->is_available = $isAvailable;
        $leadAllocation->save();
    }

    public function getUnAllocatedLeads()
    {
        $unAllocatedLeads = [];
        $to = Carbon::now();
        $from = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_START_DATE_FOR_LEADS')->first()->value;
        Log::channel('daily')->info('to date: ' . $to . ' from date: ' . $from);
        $unAllocatedLeads = HealthQuote::select('health_quote_request.*')
            ->join('quote_status', 'quote_status.id', '=', 'health_quote_request.quote_status_id')
            ->where('quote_status.text', 'Qualified')
            ->whereNotNull('health_quote_request.health_team_type')
            ->whereNull('health_quote_request.advisor_id')
            ->whereBetween('health_quote_request.created_at', [$from, $to])->skip(0)->take(50)->get();
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

    public function assignLead($lead, $advisorId)
    {
        Log::channel('daily')->info('assignLead -- started');
        $lead->advisor_id = $advisorId;
        $lead->save();
        Log::channel('daily')->info('Lead Id ' . $lead->id . ' assigned to advisor ' . $advisorId);
        $this->updateLeadAllocationRecord($advisorId);
    }

    public function updateLeadAllocationRecord($userId)
    {
        Log::channel('daily')->info('updateLeadAllocationRecord -- started');
        $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
        $leadAllocation->allocation_count += 1;
        $leadAllocation->save();
        Log::channel('daily')->info('Lead allocation record for user ' . $userId . ' updated. Current allocation count is ' . $leadAllocation->allocation_count);
    }

    public function getHealthUserSubTeamName($userId)
    {
        Log::channel('daily')->info('getHealthUserSubTeamName -- started');
        $user = User::find($userId);
        $userSubTeam = Team::find($user->sub_team_id)->first();
        Log::channel('daily')->info('User ' . $user->name . ' belongs to sub team ' . $userSubTeam->name);
        return strtolower($userSubTeam->name);
    }

    public function setAdvisorsToUnavailable()
    {
        Log::channel('daily')->info('setAdvisorsToUnavailable -- started');
        $dateTimeNow = Carbon::now()->toTimeString();
        Log::channel('daily')->info('Current time is ' . $dateTimeNow);
        $timeForUnavailability = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_UNAVAILABILITY_TIME')->first()->value;
        if ($dateTimeNow >= $timeForUnavailability) {
            Log::channel('daily')->info('Current time before unavailable is ' . $dateTimeNow);
            Log::channel('daily')->info('Setting advisors to unavailable');
            $leadAllocations = LeadAllocation::where('is_available', '=', true)->get();
            foreach ($leadAllocations as $leadAllocation) {
                $leadAllocation->is_available = false;
                $leadAllocation->allocation_count = 0;
                $leadAllocation->save();
                Log::channel('daily')->info('Advisor ' . $leadAllocation->user_id . ' is now unavailable');
            }
        }
    }
}
