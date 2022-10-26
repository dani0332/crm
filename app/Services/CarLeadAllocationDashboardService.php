<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\Teams;
use App\Models\Tier;
use App\Models\User;
use Carbon\Carbon;
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
                                'users.name as userName', DB::RAW('GROUP_CONCAT(DISTINCT (t.name)) AS tiers'), DB::RAW('GROUP_CONCAT(DISTINCT (q.name)) AS quads'),
                                'la.allocation_count as allocationCount', 'la.last_allocated as lastAllocation', 'la.max_capacity as maxCapacity', 'la.is_available as isAvailable',
                                'users.last_login as lastLogin', 'la.id as id'
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

    public function generateAdvisorConversionReportData()
    {
        $batchList = $this->generateReportBatches();
        $carTeamId = Teams::where('name', quoteTypeCode::Car)->first()->id;
        $teams = Teams::where('parent_team_id', $carTeamId)->get();
        $leadSources = LeadSource::where('is_active', 1)->get();
        $users = User::where('team_id', $carTeamId)->where('is_active', 1)->get();
        $tiers = Tier::where('is_active', 1)->get();
    }

    public function getTodaysCarTotalLeadsCount()
    {
        $from = Carbon::now()->startOfDay();
        $to = Carbon::now()->endOfDay();
        return CarQuote::whereBetween('created_at', [$from, $to])
            ->where('quote_status_id' , '!=', QuoteStatusEnum::Fake)
            ->where('source' , '!=', LeadSourceEnum::IMCRM)
            ->count();
    }
}
