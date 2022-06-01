<?php

namespace App\Services;

use App\Models\LeadAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Config;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use DB;



class LeadAllocationService extends BaseService
{
    public function getGridData(Request $request)
    {
        $userAgainstManagerWithDetail = DB::table('lead_allocation as la')
            ->select('la.user_id as userId', 'la.allocation_count', 'la.max_capacity', 'la.is_available', 'la.last_allocation_date', 't.name as teamName', 'u.name as userName')
            ->join('users as u', 'la.user_id', '=', 'u.id')
            ->leftjoin('teams as t', 'u.team_id', '=', 't.id')
            ->where('u.manager_id', '=', $request->user()->id)
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
}
