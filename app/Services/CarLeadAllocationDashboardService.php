<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TiersEnum;
use App\Models\CarQuote;
use App\Models\User;
use App\Models\UserManager;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CarLeadAllocationDashboardService extends BaseService
{
    use TeamHierarchyTrait;

    protected $applicationStorageService;
    public function __construct(ApplicationStorageService $applicationStorageService)
    {
        $this->applicationStorageService = $applicationStorageService;
    }

    public function getGridData()
    {
        try {
            $users = User::join('tier_users as tu', 'tu.user_id', 'users.id')
                ->join('tiers as t', 't.id', 'tu.tier_id')
                ->leftJoin('quad_users as qu', 'qu.user_id', 'users.id')
                ->leftJoin('quadrants as q', 'q.id', 'qu.quad_id')
                ->join('lead_allocation as la', 'la.user_id', 'users.id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', 'user_team.team_id')
                ->where('users.is_ai_user', false)
                ->activeUser()
                ->where('la.quote_type_id', QuoteTypes::CAR->id())
                ->groupBy('users.name', 'users.id', 'la.id')
                ->select(
                    'users.id as userId',
                    'users.name as userName',
                    DB::RAW('GROUP_CONCAT(DISTINCT (t.name)) AS tiers'),
                    DB::RAW('GROUP_CONCAT(DISTINCT (q.name)) AS quads'),
                    DB::RAW('(la.manual_assignment_count  + la.auto_assignment_count) as allocationCount'),
                    DB::RAW("DATE_FORMAT(FROM_UNIXTIME(la.last_allocated), '%d-%m-%Y %H:%i:%s') as lastAllocation"),
                    'la.max_capacity as maxCapacity',
                    'users.status as isAvailable',
                    DB::RAW("DATE_FORMAT(users.last_login, '%d-%m-%Y %H:%i:%s') as lastLogin"),
                    'la.id as id',
                    'la.manual_assignment_count as manualAllocationCount',
                    'la.auto_assignment_count as autoAllocationCount',
                    'la.reset_cap',
                    'la.buy_lead_max_capacity as BLMaxCapacity',
                    'la.buy_lead_allocation_count as BLAllocationCount',
                    'la.buy_lead_cat_a_allocation_count as BLCATAAllocationCount',
                    'la.buy_lead_status as BLStatus',
                    'la.normal_allocation_enabled as normalAllocationEnabled',
                    'la.buy_lead_reset_capacity as blResetCap',
                );
            $managerDepartmentIds = null;
            if (auth()->user()->hasAnyRole([RolesEnum::ManagerLeadAllocationEdit, RolesEnum::ManagerLeadAllocation])
                && ! auth()->user()->hasAnyRole([RolesEnum::Admin, RolesEnum::SuperManagerLeadAllocation])) {
                $managerDepartmentIds = auth()->user()->department()->pluck('id')->toArray();
                $users = $users->whereIn('users.department_id', $managerDepartmentIds);
            } else {
                if (! auth()->user()->hasRole(RolesEnum::Admin)) {
                    $userTeamIds = $this->getUserTeams(auth()->user()->id)->pluck('id')->toArray();
                    $users = $users->whereIn('teams.id', $userTeamIds);
                }
                if (! auth()->user()->hasRole(RolesEnum::SuperManagerLeadAllocation)) {
                    $userIds = UserManager::where('manager_id', Auth::id())->pluck('user_id')->toArray();
                    $users = $users->whereIn('users.id', $userIds);
                }
            }

            $aiUsers = User::join('lead_allocation as la', 'la.user_id', 'users.id')
                ->activeUser()
                ->where('la.quote_type_id', QuoteTypes::CAR->id())
                ->where('users.is_ai_user', true)
                ->groupBy('users.name', 'users.id', 'la.id')
                ->select(
                    'users.id as userId',
                    'users.name as userName',
                    DB::RAW('NULL AS tiers'),
                    DB::RAW('NULL AS quads'),
                    DB::RAW('(la.manual_assignment_count  + la.auto_assignment_count) as allocationCount'),
                    DB::RAW("DATE_FORMAT(FROM_UNIXTIME(la.last_allocated), '%d-%m-%Y %H:%i:%s') as lastAllocation"),
                    'la.max_capacity as maxCapacity',
                    'users.status as isAvailable',
                    DB::RAW("DATE_FORMAT(users.last_login, '%d-%m-%Y %H:%i:%s') as lastLogin"),
                    'la.id as id',
                    'la.manual_assignment_count as manualAllocationCount',
                    'la.auto_assignment_count as autoAllocationCount',
                    'la.reset_cap',
                    'la.buy_lead_max_capacity as BLMaxCapacity',
                    'la.buy_lead_allocation_count as BLAllocationCount',
                    'la.buy_lead_cat_a_allocation_count as BLCATAAllocationCount',
                    'la.buy_lead_status as BLStatus',
                    'la.normal_allocation_enabled as normalAllocationEnabled',
                    'la.buy_lead_reset_capacity as blResetCap',
                );
            if ($managerDepartmentIds !== null) {
                $aiUsers = $aiUsers->whereIn('users.department_id', $managerDepartmentIds);
            }

            return $users->union($aiUsers)->get();
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
        return CarQuote::leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->whereNull('advisor_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where('tiers.name', '!=', TiersEnum::TIER_R)
            ->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->subMinutes(2)->toDateTimeString()])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::REVIVAL])
            ->whereNotIn('car_quote_request.uuid', function ($query) { // to remove from the query tags table to exlude SIC records from the result set
                $query->distinct()
                    ->select('quote_uuid')
                    ->from('quote_tags')
                    ->join('quote_type', 'quote_type.id', 'quote_tags.quote_type_id')
                    ->where('quote_tags.name', 'SIC')
                    ->where('quote_type.code', quoteTypeCode::Car);
            })
            ->count();
    }
}
