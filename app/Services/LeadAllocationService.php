<?php

namespace App\Services;

use App\Models\LeadAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Config;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class LeadAllocationService extends BaseService
{
    public function getGridData(Request $request)
    {
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
