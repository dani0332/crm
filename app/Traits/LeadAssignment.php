<?php

namespace App\Traits;

use App\Models\LeadAllocation;
use App\Models\User;
use Carbon\Carbon;

trait AssignLead
{

    function processAllocation($user, $lead)
    {
        $userAllocationRecord = LeadAllocation::where('user_id', $user->id)->first();
        if (!$userAllocationRecord) {
            $userAllocationRecord = new LeadAllocation();
            $userAllocationRecord->user_id = $user->id;
            $userAllocationRecord->allocation_count = 1;
            $userAllocationRecord->is_available = 1;
            $userAllocationRecord->last_allocated = Carbon::now()->timestamp;
            $userAllocationRecord->created_at = Carbon::now();
            $userAllocationRecord->updated_at = Carbon::now();
            $userAllocationRecord->save();
        }
        if ($userAllocationRecord->allocation_count < $userAllocationRecord->max_capacity || $userAllocationRecord->max_capacity == -1) {
            $userAllocationRecord->allocation_count = $userAllocationRecord->allocation_count + 1;
            $userAllocationRecord->last_allocated = Carbon::now()->timestamp;
            $userAllocationRecord->save();
        }

        $lead->advisor_id = $user->id;
        $lead->save();
        return 'True';
    }
}
