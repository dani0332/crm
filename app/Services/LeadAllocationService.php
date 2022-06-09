<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Models\LeadAllocation;
use App\Traits\GetUserTree;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;
use Auth;
use Illuminate\Support\Facades\Log;

class LeadAllocationService extends BaseService
{
    use GetUserTree;
    protected $crudService;

    public function __construct(CRUDService $crudService)
    {
        $this->crudService = $crudService;
    }

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
        $leadAllocation->is_available = true;
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
}
