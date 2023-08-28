<?php

namespace App\Services;

use App\Models\ApplicationStorage;
use App\Models\Tier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AllocationService
{
    public function getAppStorageValueByKey($keyName)
    {
        $query = ApplicationStorage::select('value')
            ->where('key_name', $keyName)
            ->first();

        if (! $query) {
            return false;
        }

        return $query->value;
    }

    public function getTierById($tierId)
    {
        return Tier::where('id', $tierId)->first();
    }

    public function updateLeadAllocationCounts($userId): void
    {
        $timestamp = Carbon::now()->timestamp;

        DB::table('lead_allocation')
            ->where('user_id', $userId)
            ->update([
                'allocation_count' => DB::raw('allocation_count + 1'),
                'auto_assignment_count' => DB::raw('auto_assignment_count + 1'),
                'last_allocated' => $timestamp,
                'updated_at' => now(),
            ]);
    }
}
