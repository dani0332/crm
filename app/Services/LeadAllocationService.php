<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Models\ApplicationStorage;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\LeadAllocation;
use App\Models\Team;
use App\Models\User;
use App\Traits\GetUserTree;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Log;

class LeadAllocationService extends BaseService
{
    use GetUserTree;


    public function getGridData()
    {
        try {
            DB::beginTransaction();
            $userAgainstManagerWithDetail = LeadAllocation::select('lead_allocation.id as id', 'lead_allocation.user_id as userId', 'lead_allocation.allocation_count', 'lead_allocation.max_capacity', 'lead_allocation.is_available', 'lead_allocation.last_allocated', 'st.name as teamName', 'u.name as userName')
                ->join('users as u', 'lead_allocation.user_id', '=', 'u.id')
                ->leftjoin('teams as t', 'u.team_id', '=', 't.id')
                ->leftjoin('teams as st', 'st.id', '=', 'u.sub_team_id')
                ->where(strtolower('t.name'), '=', strtolower(quoteTypeCode::Health));
            if(!auth()->user()->hasRole(RolesEnum::SuperManagerLeadAllocation)){
                $userAgainstManagerWithDetail = $userAgainstManagerWithDetail->where('u.manager_id', auth()->user()->id);
            }
            DB::commit();
            return $userAgainstManagerWithDetail->get();
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
            $leadAllocation->last_allocated = now()->timestamp;
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
            $to = now();
            $from = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_START_DATE_FOR_LEADS')->first()->value;
            info('from date : ' . $from . ' to date : ' . $to);
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
            info('getNextAvailableAdvisors -- started');

            $healthTeamId = Team::where('name', quoteTypeCode::Health)->first()->id;
            $healthSubTeamIds = Team::where('parent_team_id', $healthTeamId)->get()->pluck('id');

            $healthUsersQuery = User::
                 whereNotNull('users.sub_team_id')
                ->where('users.is_active', true)
                ->whereIn('users.sub_team_id', $healthSubTeamIds);
            $healthUsers = $healthUsersQuery->pluck('users.id');

            info('Found ' . count($healthUsers) . ' sub-ordinates');

            info('Fetching lead allocation records for sub-ordinates');
            $leadAllocationWithUsers = LeadAllocation::with('leadAllocationUser')
                ->where('is_available', '=', true)
                ->whereIn('user_id', $healthUsers)
                ->get();
            info('Found ' . count($leadAllocationWithUsers) . ' lead allocation records');

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

        info('getAssignableUsers -- started');
        $allAssignableUsers = collect([]);
        foreach ($leadAllocationWithUsers as $leadAllocationWithUser) {
            if ($leadAllocationWithUser->allocation_count < $leadAllocationWithUser->max_capacity || $leadAllocationWithUser->max_capacity == -1) {
                info('Found assignable user ' . $leadAllocationWithUser->leadAllocationUser->name);
                $allAssignableUsers->push($leadAllocationWithUser->leadAllocationUser->id);
            }
        }
        info('Found ' . count($allAssignableUsers) . ' assignable users');
        $allAssignableUsers = $allAssignableUsers->sortBy('last_allocated', SORT_NATURAL);
        if ($allAssignableUsers->count() > 0) {
            info('Returning assignable user count : ' . $allAssignableUsers->count());
            return $allAssignableUsers;
        }
        return collect([]);
    }

    public function assignLead($lead, $advisorId, $isManualAssignment = false)
    {
        info('assignLead -- started');
        if($this->checkIfAdvisorCanTakeLead($advisorId)){
            if ($lead->advisor_id != null) {
                $this->removeLeadAllocationForOldAdvisor($lead);
            }

            info('Assigning lead ' . $lead->id . ' to advisor ' . $advisorId);
            try {
                DB::beginTransaction();

                if ($isManualAssignment && $lead->advisor_id != null) {
                    $lead->quote_status_id = QuoteStatusEnum::Qualified;
                }
                $lead->advisor_id = $advisorId;
                $lead->save();
                info('Lead Id ' . $lead->id . ' assigned to advisor ' . $advisorId);
                $this->updateLeadAllocationRecord($advisorId);
                $this->updateLeadDetailRecord($lead->id); // TODO : add LOB type when implement for other lines
                DB::commit(); // added commit before
                return true;

            } catch (\Exception $e) {
                Log::error($e->getMessage());
                DB::rollback();
            }
        }
        else{
            return false;
        }
    }

    public function updateLeadDetailRecord($leadId)
    {
        info('updateLeadDetailRecord -- started for lead id: ' . $leadId);
        $leadDetail = HealthQuoteRequestDetail::where('health_quote_request_id',$leadId)->first();
        if($leadDetail){
            $leadDetail->advisor_assigned_date = now();
            $leadDetail->advisor_assigned_by_id = auth()->id();
            $leadDetail->save();
        }
        info('updateLeadDetailRecord -- completed for lead id: ' . $leadId);
    }

    public function removeLeadAllocationForOldAdvisor($lead)
    {

        try {
            DB::beginTransaction();
            info('removeLeadAllocationForOldAdvisor -- started');
            info('Removing lead allocation record for lead id: ' . $lead->id . ' and advisor id: ' . $lead->advisor_id);
            LeadAllocation::where('user_id', $lead->advisor_id)->decrement('allocation_count', 1);
            info('Lead allocation count decremented for advisor id: ' . $lead->advisor_id);
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }
    public function checkIfAdvisorCanTakeLead($advisorId)
    {
        try {
            DB::beginTransaction();
            info('checkIfAdvisorCanTakeLead -- started');
            $leadAllocation = LeadAllocation::where('user_id', $advisorId)->first();
            if ($leadAllocation != null) {
                if($leadAllocation->max_capacity == -1 || $leadAllocation->allocation_count < $leadAllocation->max_capacity){
                    info('Advisor ' . $advisorId . ' can take lead');
                    DB::commit();
                    return true;
                }
                if ($leadAllocation->max_capacity == $leadAllocation->allocation_count && $leadAllocation->max_capacity != -1) {
                    info('Advisor ' . $advisorId . ' cannot take lead. Max capacity reached');
                    DB::commit();
                    return false;
                }
            } else {
                info('Advisor ' . $advisorId . ' has no allocation record');
                DB::commit();
                return false;
            }

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
            throw $e;
        }
    }
    public function updateLeadAllocationRecord($userId)
    {
        try {
            DB::beginTransaction();
            info('updateLeadAllocationRecord -- started');
            $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
            DB::commit();
            info('Max capacity for user ' . $userId . ' is ' . $leadAllocation->max_capacity. ' and allocation count is ' . $leadAllocation->allocation_count);
            if($leadAllocation->max_capacity > $leadAllocation->allocation_count){
                $leadAllocation->allocation_count += 1;
                $leadAllocation->last_allocated = now()->timestamp;
                $leadAllocation->save();
                info('Lead allocation record for user ' . $userId . ' updated. Current allocation count is ' . $leadAllocation->allocation_count);
            }else{
                info('Lead allocation record not updated for user ' . $userId . ' updated for lead because max cap reached.');
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
            info('getHealthUserSubTeamName -- started');
            $user = User::where('id', $userId)->first();
            if ($user) {
                if($user->sub_team_id != null){
                    info('User ' . $user->name . ' has sub-team ' . $user->sub_team_id);
                    $userSubTeam = Team::where('id', $user->sub_team_id)->first();
                    info('User ' . $user->name . ' belongs to sub team ' . $userSubTeam->name);
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
            info('setAdvisorsToUnavailable -- started');
            $dateTimeNow = now()->toTimeString();
            info('Current time is ' . $dateTimeNow);
            $timeForUnavailability = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_UNAVAILABILITY_TIME')->first()->value;
            if ($dateTimeNow >= $timeForUnavailability) {
                info('Current time before unavailable is ' . $dateTimeNow);
                info('Setting advisors to unavailable');
                $leadAllocations = LeadAllocation::get();
                foreach ($leadAllocations as $leadAllocation) {
                    $leadAllocation->is_available = false;
                    $leadAllocation->allocation_count = 0;
                    $leadAllocation->save();
                    info('Advisor ' . $leadAllocation->user_id . ' is now unavailable and allocation count is set to 0');
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
