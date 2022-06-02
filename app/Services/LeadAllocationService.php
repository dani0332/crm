<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Models\LeadAllocation;
use App\Traits\GetUserTree;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Config;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use DB;
use Auth;



class LeadAllocationService extends BaseService
{
    use GetUserTree;
    public function getGridData(Request $request)
    {
        $userAgainstManagerWithDetail = DB::table('lead_allocation as la')
            ->select('la.user_id as userId', 'la.allocation_count', 'la.max_capacity', 'la.is_available', 'la.last_allocation_date', 't.name as teamName', 'u.name as userName')
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

    public function assignNewLead()
    {
        $subOrdinates = $this->walkTree(Auth::user()->id);

        $leadAllocationWithUsers = LeadAllocation::with('leadAllocationUser')->where('is_available', '=', true)->whereIn('user_id', $subOrdinates)->get();

        $nextAvailableUser = $this->getNextAssignableUser($leadAllocationWithUsers);

        dd($nextAvailableUser);

        return $nextAvailableUser;
    }

    public function getNextAssignableUser($leadAllocationWithUsers)
    {
        $allAssignableUsers = collect([]);
        foreach ($leadAllocationWithUsers as $leadAllocationWithUser) {
            if ($leadAllocationWithUser->allocation_count < $leadAllocationWithUser->max_capacity || $leadAllocationWithUser->max_capacity == -1) {
                $allAssignableUsers->push($leadAllocationWithUser);
            }
        }

        $allAssignableUsers = $allAssignableUsers->sortBy('last_allocated', SORT_NATURAL);
        if ($allAssignableUsers->count() > 0) {
            return $allAssignableUsers->first();
        }
        return 0;
    }
}
