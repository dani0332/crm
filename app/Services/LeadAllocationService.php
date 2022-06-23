<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
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
        try {
            DB::beginTransaction();
            $userAgainstManagerWithDetail = DB::table('lead_allocation as la') // TODO : convert it to eloquent query
                ->select('la.id as id', 'la.user_id as userId', 'la.allocation_count', 'la.max_capacity', 'la.is_available', 'la.last_allocated', 'st.name as teamName', 'u.name as userName')
                ->join('users as u', 'la.user_id', '=', 'u.id')
                ->leftjoin('teams as t', 'u.team_id', '=', 't.id')
                ->leftjoin('teams as st', 'st.id', '=', 'u.sub_team_id')
                ->where('u.manager_id', '=', $request->user()->id)
                ->where(strtolower('t.name'), '=', strtolower(quoteTypeCode::Health))
                ->get();
            DB::commit();
            return $userAgainstManagerWithDetail;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function createLeadAllocationRecord($userId)
    {

        try {
            DB::beginTransaction();
            $leadAllocation = new LeadAllocation();
            $leadAllocation->user_id = $userId;
            $leadAllocation->allocation_count = 0;
            $leadAllocation->last_allocated = Carbon::now()->timestamp;
            $leadAllocation->max_capacity = 0;
            $leadAllocation->is_available = false;
            $leadAllocation->save();
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function updateUserAllocationRecord($userId, $allocationCount, $maxCapacity, $isAvailable)
    {

        try {
            DB::beginTransaction();
            $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
            if (isset($allocationCount)) $leadAllocation->allocation_count = $allocationCount;
            if (isset($max_capacity)) $leadAllocation->max_capacity = $maxCapacity;
            if (isset($isAvailable)) $leadAllocation->is_available = $isAvailable;
            $leadAllocation->save();
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function getUnAllocatedLeads()
    {
        try {
            DB::beginTransaction();
            $unAllocatedLeads = [];
            $to = Carbon::now();
            $from = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_START_DATE_FOR_LEADS')->first()->value;
            Log::info('to date: ' . $to . ' from date: ' . $from);
            $unAllocatedLeads = HealthQuote::select('health_quote_request.*')
                ->join('quote_status', 'quote_status.id', '=', 'health_quote_request.quote_status_id')
                ->where('quote_status.id', QuoteStatusEnum::Qualified)
                ->whereNotNull('health_quote_request.health_team_type')
                ->whereNull('health_quote_request.advisor_id')
                ->whereBetween('health_quote_request.created_at', [$from, $to])->skip(0)->take(50)->get();

            DB::commit();
            return $unAllocatedLeads;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function getNextAvailableAdvisors()
    {
        try {
            DB::beginTransaction();
            Log::info('getNextAvailableAdvisors -- started');
            Log::info('Fetching loggedin users with Health Team and roles RM, EBP & HEALTH advisor');
            $healthTeamId = Team::where('name', quoteTypeCode::Health)->first()->id;
            $healthUsersQuery = User::join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereIn('roles.name', [RolesEnum::RMAdvisor, RolesEnum::EBPAdvisor, RolesEnum::HealthAdvisor])
                ->whereNotNull('users.sub_team_id')
                ->where('users.is_active', true)
                ->where('users.team_id', $healthTeamId);
            $healthUsers = $healthUsersQuery->pluck('users.id');
            Log::info('Found ' . count($healthUsers) . ' sub-ordinates');
            Log::info('Fetching lead allocation records for sub-ordinates');
            $leadAllocationWithUsers = LeadAllocation::with('leadAllocationUser')
                ->where('is_available', '=', true)
                ->whereIn('user_id', $healthUsers)
                ->get();
            Log::info('Found ' . count($leadAllocationWithUsers) . ' lead allocation records');

            DB::commit();
            $availableUsers = $this->getAssignableUsers($leadAllocationWithUsers);
            return $availableUsers;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function getAssignableUsers($leadAllocationWithUsers)
    {

        Log::info('getAssignableUsers -- started');
        $allAssignableUsers = collect([]);
        foreach ($leadAllocationWithUsers as $leadAllocationWithUser) {
            if ($leadAllocationWithUser->allocation_count < $leadAllocationWithUser->max_capacity || $leadAllocationWithUser->max_capacity == -1) {
                Log::info('Found assignable user ' . $leadAllocationWithUser->leadAllocationUser->name);
                $allAssignableUsers->push($leadAllocationWithUser->leadAllocationUser->id);
            }
        }
        Log::info('Found ' . count($allAssignableUsers) . ' assignable users');
        $allAssignableUsers = $allAssignableUsers->sortBy('last_allocated', SORT_NATURAL);
        if ($allAssignableUsers->count() > 0) {
            Log::info('Returning assignable user count : ' . $allAssignableUsers->count());
            return $allAssignableUsers;
        }
        return new User();
    }

    public function assignLead($lead, $advisorId, $isManualAssignment = false)
    {
        if ($lead->advisor_id != null) {
            $this->removeLeadAllocationForOldAdvisor($lead);
        }
        Log::info('assignLead -- started');
        Log::info('Assigning lead ' . $lead->id . ' to advisor ' . $advisorId);
        try {
            DB::beginTransaction();

            if ($isManualAssignment && $lead->advisor_id != null) {
                $lead->quote_status_id = QuoteStatusEnum::Qualified;
            }

            $lead->advisor_id = $advisorId;

            $lead->save();
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }

        Log::info('Lead Id ' . $lead->id . ' assigned to advisor ' . $advisorId);

        $this->updateLeadAllocationRecord($advisorId);
    }

    public function removeLeadAllocationForOldAdvisor($lead)
    {

        try {
            DB::beginTransaction();
            Log::info('removeLeadAllocationForOldAdvisor -- started');
            $leadAllocation = LeadAllocation::where('user_id', $lead->advisor_id)->first();
            if ($leadAllocation != null) {
                $leadAllocation->allocation_count = $leadAllocation->allocation_count - 1;
                $leadAllocation->save();
                Log::info('Lead allocation record for user ' . $$lead->advisor_id . ' updated. Current allocation count is ' . $leadAllocation->allocation_count);
            } else {
                Log::info('Lead allocation record for user ' . $$lead->advisor_id . ' not found for minus 1 allocation count');
            }
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function updateLeadAllocationRecord($userId)
    {
        try {
            DB::beginTransaction();
            Log::info('updateLeadAllocationRecord -- started');
            $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
            if($leadAllocation->max_cap < $leadAllocation->allocation_count){
                $leadAllocation->allocation_count += 1;
                $leadAllocation->last_allocated = Carbon::now()->timestamp;
                $leadAllocation->save();
                Log::info('Lead allocation record for user ' . $userId . ' updated. Current allocation count is ' . $leadAllocation->allocation_count);
                DB::commit();
            }else{
                Log::info('Lead allocation record not updated for user ' . $userId . ' updated for lead because max cap reached.');
            }

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function getHealthUserSubTeamName($userId)
    {
        try {
            DB::beginTransaction();
            Log::info('getHealthUserSubTeamName -- started');
            $user = User::where('id', $userId)->first();
            if ($user) {
                if($user->sub_team_id != null){
                    Log::info('User ' . $user->name . ' has sub-team ' . $user->sub_team_id);
                    $userSubTeam = Team::where('id', $user->sub_team_id)->first();
                    Log::info('User ' . $user->name . ' belongs to sub team ' . $userSubTeam->name);
                    DB::commit();
                    return strtolower($userSubTeam->name);
                }else{
                    return null;
                }
            } else {
                DB::commit();
                return null;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function setAdvisorsToUnavailable()
    {
        try {
            DB::beginTransaction();
            Log::info('setAdvisorsToUnavailable -- started');
            $dateTimeNow = Carbon::now()->toTimeString();
            Log::info('Current time is ' . $dateTimeNow);
            $timeForUnavailability = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_UNAVAILABILITY_TIME')->first()->value;
            if ($dateTimeNow >= $timeForUnavailability) {
                Log::info('Current time before unavailable is ' . $dateTimeNow);
                Log::info('Setting advisors to unavailable');
                $leadAllocations = LeadAllocation::get();
                foreach ($leadAllocations as $leadAllocation) {
                    $leadAllocation->is_available = false;
                    $leadAllocation->allocation_count = 0;
                    $leadAllocation->save();
                    Log::info('Advisor ' . $leadAllocation->user_id . ' is now unavailable and allocation count is set to 0');
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function leadAllocationSwitchStatus()
    {
        try {
            DB::beginTransaction();
            $leadAllocationSwitch = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_JOB_SWITCH')->first();
            DB::commit();
            return $leadAllocationSwitch && $leadAllocationSwitch->value == '1';
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function getLeadAllocationRecordByUserId($userId)
    {
        try {
            DB::beginTransaction();
            $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
            DB::commit();
            return $leadAllocation;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }
}
