<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;

class CarLeadAllocationDashboardService extends BaseService
{
    public function getGridData()
    {
        try {
            $users = User::join('tier_users as tu', 'tu.user_id', 'users.id')
                            ->join('tiers as t', 't.id', 'tu.tier_id')
                            ->leftJoin('quad_users as qu', 'qu.user_id', 'users.id')
                            ->leftJoin('quadrants as q', 'q.id', 'qu.quad_id')
                            ->join('lead_allocation as la', 'la.user_id', 'users.id')
                            ->where('users.is_active', 1)
                            ->groupBy('users.name', 'users.id', 'la.id')
                            ->select(
                                'users.id as userId',
                                'users.name as userName', DB::RAW('GROUP_CONCAT(DISTINCT (t.name)) AS tiers'), DB::RAW('GROUP_CONCAT(DISTINCT (q.name)) AS quads'),
                                'la.allocation_count as allocationCount', 'la.last_allocated as lastAllocation', 'la.max_capacity as maxCapacity', 'la.is_available as isAvailable',
                                'users.last_login as lastLogin', 'la.id as id', 'la.manual_assignment_count as manualAllocationCount', 'la.auto_assignment_count as autoAllocationCount'
                            );
            if (! auth()->user()->hasRole(RolesEnum::SuperManagerLeadAllocation)) {
                $users = $users->where('users.manager_id', auth()->user()->id);
            }

            return $users->get();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function generateReportBatches()
    {
        $batchStartDate = ApplicationStorage::where('key_name', 'CONVERSATION_REPORT_BATCH_START_DATE')->first()->value;
        $startDate = Carbon::parse($batchStartDate);
        $endDate = Carbon::parse($batchStartDate);
        $batchList = [];
        $batchCount = 1;
        while ($endDate <= now()) {
            $currentWeek = $startDate->format('Y-m-d');
            $nextWeek = $startDate->addWeek(1)->addDay(1)->format('Y-m-d');
            $batchString = 'Batch-'.$batchCount.'-('.$currentWeek.' to '.$nextWeek.')';
            array_push($batchList, [$currentWeek.','.$nextWeek => $batchString]);
            $endDate = $startDate;
            $batchCount++;
        }

        return $batchList;
    }

    public function getTodaysCarTotalLeadsCount()
    {
        $from = Carbon::now()->startOfDay();
        $to = Carbon::now()->endOfDay();

        return CarQuote::whereBetween('created_at', [$from, $to])
            ->where('quote_status_id', '!=', QuoteStatusEnum::Fake)
            ->where('source', '!=', LeadSourceEnum::IMCRM)
            ->count();
    }
    public function getTodaysCarTotalUnAssignedLeadsCount()
    {
        $from = Carbon::now()->startOfDay();
        $to = Carbon::now()->endOfDay();

        return CarQuote::whereBetween('created_at', [$from, $to])
            ->where('quote_status_id', '!=', QuoteStatusEnum::Fake)
            ->whereNull('advisor_id')
            ->count();
    }
}
