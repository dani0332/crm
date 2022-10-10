<?php

namespace App\Services;

use App\Enums\RolesEnum;
use App\Models\User;
use DB;
use Illuminate\Support\Facades\Log;

class CarLeadAllocationDashboardService extends BaseService
{
    public function getGridData()
    {
        try {
            DB::beginTransaction();
            $users = User::join('tier_users as tu', 'tu.user_id', 'users.id')
                            ->join('tiers as t', 't.id', 'tu.tier_id')
                            ->leftJoin('quad_users as qu', 'qu.user_id', 'users.id')
                            ->leftJoin('quadrants as q', 'q.id', 'qu.quad_id')
                            ->join('lead_allocation as la', 'la.user_id', 'users.id')
                            ->where('users.is_active', 1)
                            ->groupBy('users.name', 'users.id', 'la.id')
                            ->select(
                                'users.id as userId',
                                'users.name as userName', DB::RAW('GROUP_CONCAT(DISTINCT (t.name)) AS tiers'), DB::RAW('GROUP_CONCAT(DISTINCT (q.name)) AS quads'), 'la.allocation_count as allocationCount', 'la.last_allocated as lastAllocation', 'la.max_capacity as maxCapacity', 'la.is_available as isAvailable', 'users.last_login as lastLogin', 'la.id as id'
                            );
            if (! auth()->user()->hasRole(RolesEnum::SuperManagerLeadAllocation)) {
                $users = $users->where('users.manager_id', auth()->user()->id);
            }
            DB::commit();

            return $users->get();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }
}
