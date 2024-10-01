<?php

namespace App\Http\Controllers;

use App\Enums\IMCRMSearchTypesEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TiersEnum;
use App\Models\CarQuote;
use App\Models\Team;
use App\Models\Tier;
use App\Models\UserManager;
use App\Services\ComprehensiveConversionDashboardService;
use App\Services\DashboardService;
use App\Services\TierService;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    protected $dashboardService;
    protected $tierService;

    public function __construct(DashboardService $dashboardService, TierService $tierService)
    {
        $this->dashboardService = $dashboardService;
        $this->tierService = $tierService;

        $comprehensiveDashboardPermissions = implode('|', PermissionsEnum::getComprehensiveDashboardPermissions());
        $this->middleware(['permission:'.$comprehensiveDashboardPermissions], ['only' => ['renderComprehensiveDashboard']]);

        $this->middleware('readonly_db');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard');
    }

    public function renderMainDashboard()
    {
        $loggedInUserId = auth()->user()->id;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $startDate = now()->startOfDay()->format($dateFormat);
        $endDate = now()->endOfDay()->format($dateFormat);

        $teams = $this->getCurrentUserTeamsAndSubTeams($loggedInUserId);
        $teamIds = DB::table('user_team')->where('user_id', $loggedInUserId)->get()->pluck('team_id');
        $carAdvisors = $this->getUsersByTeamId(count($teamIds->toArray()) > 0 ? $teamIds->toArray() : []);

        $filters = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'teams' => $teams,
            'teamIds' => $teamIds,
            'applyUnAssignedLeadsCountByTierDateFilter' => false,
            'applyTotalUnAssignedLeadsDateFilter' => false,
        ];

        $assignedLeadsBySource = $this->dashboardService->getAssignedLeadsCountBySource($filters);

        [
            'teamWiseLeadsAssignedAverage' => $teamWiseLeadsAssignedAverage,
            'totalLeadsReceived' => $totalLeadsReceived,
            'totalLeadsReceivedEcommerce' => $totalLeadsReceivedEcommerce,
            'totalUnassignedLeadsReceived' => $totalUnAssignedLeadsReceived,
            'totalUnassignedLeadsReceivedEcommerce' => $totalUnAssignedLeadsReceivedEcommerce,
            'totalUnassignedRevivalLeads' => $totalUnAssignedRevivalLeads,
            'totalUnAssignedOnlySICLeadsReceived' => $totalUnAssignedOnlySICLeadsReceived,
            'totalUnAssignedOnlyPaidSICLeadsReceived' => $totalUnAssignedOnlyPaidSICLeadsReceived,
            'leadsCountByTier' => $leadsCountByTier,
            'revivalLeadsCount' => $revivalLeadsCount,
            'unAssignedLeadsByTier' => $unAssignedLeadsByTier,
            'advisorLeadsAssignedData' => $advisorLeadsAssignedData
        ] = $this->dashboardService->getStatCounts($filters);

        $leadReceivedSummaryBySource = CarQuote::query()
            ->select(
                DB::raw('count(*) as leadSourceCount'),
                'car_quote_request.source as source',
            )
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->groupBy('source')->get();

        $leadReceivedSummaryBySource = $leadReceivedSummaryBySource->map(function ($item) use ($totalLeadsReceived) {
            $item->percentage = number_format((float) (($item->leadSourceCount / $totalLeadsReceived) * 100), 2, '.', '').'%';

            return $item;
        });

        return inertia('Dashboard/AccumulativeDashboard', [
            'totalLeadsReceived' => $totalLeadsReceived,
            'totalLeadsReceivedEcommerce' => $totalLeadsReceivedEcommerce,
            'totalUnAssignedLeadsReceived' => $totalUnAssignedLeadsReceived,
            'totalUnAssignedOnlySICLeadsReceived' => $totalUnAssignedOnlySICLeadsReceived,
            'totalUnAssignedOnlyPaidSICLeadsReceived' => $totalUnAssignedOnlyPaidSICLeadsReceived,
            'totalUnAssignedLeadsReceivedEcommerce' => $totalUnAssignedLeadsReceivedEcommerce,
            'teams' => $teams,
            'carAdvisors' => $carAdvisors,
            'teamWiseLeadsAssignedAverage' => $teamWiseLeadsAssignedAverage,
            'totalUnAssignedRevivalLeads' => $totalUnAssignedRevivalLeads,
            'leadsCountByTier' => $leadsCountByTier,
            'unAssignedLeadsByTier' => $unAssignedLeadsByTier,
            'revivalLeadsCount' => $revivalLeadsCount,
            'advisorLeadsAssignedData' => $advisorLeadsAssignedData,
            'assignedLeadsBySource' => $assignedLeadsBySource,
            'leadReceivedSummaryBySource' => $leadReceivedSummaryBySource,
        ]);
    }

    public function getRecentDailyStats(Request $request)
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        if (isset($request->range) && $request->range != null) {
            $date = explode(',', $request->range);
            $startDate = Carbon::parse($date[0])->startOfDay();
            $endDate = Carbon::parse($date[1])->endOfDay();

            // Ensure that the date range does not exceed 31 days
            if ($startDate->diffInDays($endDate) > 31) {
                $endDate = $startDate->copy()->addDays(30)->endOfDay(); // Set end date to 31 days max
            }

            // Format dates as per the $dateFormat
            $startDate = $startDate->format($dateFormat);
            $endDate = $endDate->format($dateFormat);
        } else {
            $startDate = now()->startOfDay()->format($dateFormat);
            $endDate = now()->endOfDay()->format($dateFormat);
        }

        $filters = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'teams' => $this->getCurrentUserTeamsAndSubTeams(auth()->user()->id),
            'applyUnAssignedLeadsCountByTierDateFilter' => true,
            'applyTotalUnAssignedLeadsDateFilter' => true,
        ];

        if ($request->benchmark === '1') {
            $todaysLeads = CarQuote::whereBetween('created_at', [$startDate, $endDate])
                ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
                ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO]);

            $currentLogic = Benchmark::measure([
                '$filters[teams]' => fn () => $this->getCurrentUserTeamsAndSubTeams(auth()->user()->id),
                '$teamWiseLeadsAssignedAverage' => fn () => $this->dashboardService->getTeamWiseLeadStats($filters),
                '$leadsCountByTier' => fn () => $this->dashboardService->getLeadsCountByTier($filters),
                '$revivalLeadsCount' => fn () => $this->dashboardService->getLeadsCountRevival($filters),
                '$unAssignedLeadsByTier' => fn () => $this->dashboardService->getUnAssignedLeadsCountByTier($filters),
                '$advisorLeadsAssignedData' => fn () => $this->dashboardService->getAdvisorLeadAssignedData($filters),
            ], (int) ($request->iterations ?? 1));

            $updatedLogic = Benchmark::measure([
                '$totalLeadsReceived' => fn () => $todaysLeads->count(),
                '$totalLeadsReceivedEcommerce' => fn () => $todaysLeads->where('is_ecommerce', 1)->count(),
                '$totalUnAssignedLeadsReceived' => fn () => $this->dashboardService->getTotalUnAssignedLeads($filters, true)->count(),
                '$totalUnAssignedLeadsReceivedEcommerce' => fn () => $this->dashboardService->getTotalUnAssignedLeads($filters, true)->where('is_ecommerce', 1)->count(),
                '$totalUnAssignedRevivalLeads' => fn () => $this->dashboardService->getTotalUnAssignedLeads($filters, true)->where('source', LeadSourceEnum::REVIVAL)->count(),
                '$totalUnAssignedOnlySICLeadsReceived' => fn () => $this->dashboardService->getTotalUnAssignedOnlySICLeads($filters, true)->count(),
                '$totalUnAssignedOnlyPaidSICLeadsReceived' => fn () => $this->dashboardService->getTotalUnAssignedOnlySICLeads($filters, true)->where('payment_status_id', PaymentStatusEnum::AUTHORISED)->count(),
            ], (int) ($request->iterations ?? 1));

            dd([
                'current logic' => collect($currentLogic)->map(fn ($average) => number_format($average, 3).'ms')->toArray(),
                'total time taken in current logic' => array_sum($currentLogic),
                'updated logic' => collect($updatedLogic)->map(fn ($average) => number_format($average, 3).'ms')->toArray(),
                'total time taken in updated logic' => array_sum($updatedLogic),
            ]);
        }

        [
            'teamWiseLeadsAssignedAverage' => $teamWiseLeadsAssignedAverage,
            'totalLeadsReceived' => $totalLeadsReceived,
            'totalLeadsReceivedEcommerce' => $totalLeadsReceivedEcommerce,
            'totalUnassignedLeadsReceived' => $totalUnAssignedLeadsReceived,
            'totalUnassignedLeadsReceivedEcommerce' => $totalUnAssignedLeadsReceivedEcommerce,
            'totalUnassignedRevivalLeads' => $totalUnAssignedRevivalLeads,
            'totalUnAssignedOnlySICLeadsReceived' => $totalUnAssignedOnlySICLeadsReceived,
            'totalUnAssignedOnlyPaidSICLeadsReceived' => $totalUnAssignedOnlyPaidSICLeadsReceived,
            'leadsCountByTier' => $leadsCountByTier,
            'revivalLeadsCount' => $revivalLeadsCount,
            'unAssignedLeadsByTier' => $unAssignedLeadsByTier,
            'advisorLeadsAssignedData' => $advisorLeadsAssignedData
        ] = $this->dashboardService->getStatCounts($filters);

        return [
            'totalLeadsReceived' => $totalLeadsReceived, 'totalLeadsReceivedEcommerce' => $totalLeadsReceivedEcommerce, 'totalUnAssignedLeadsReceived' => $totalUnAssignedLeadsReceived,
            'totalUnAssignedOnlySICLeadsReceived' => $totalUnAssignedOnlySICLeadsReceived, 'totalUnAssignedOnlyPaidSICLeadsReceived' => $totalUnAssignedOnlyPaidSICLeadsReceived,
            'totalUnAssignedLeadsReceivedEcommerce' => $totalUnAssignedLeadsReceivedEcommerce, 'teamWiseLeadsAssignedAverage' => $teamWiseLeadsAssignedAverage,
            'totalUnAssignedRevivalLeads' => $totalUnAssignedRevivalLeads, 'leadsCountByTier' => $leadsCountByTier, 'revivalLeadsCount' => $revivalLeadsCount, 'advisorLeadsAssignedData' => $advisorLeadsAssignedData, 'unAssignedLeadsByTier' => $unAssignedLeadsByTier,
        ];
    }

    public function renderTplDashboard(Request $request)
    {
        $tplDashboardStats = $this->getTPLDashboardStats($request);
        $car = $this->getProductByName(quoteTypeCode::Car);
        $teams = $this->getTeamsByProductId($car->id);
        $commonTeams = $this->getCommonTeamsForCurrentUserWithCar();
        $teams = $teams->filter(function ($item) use ($commonTeams) {
            return in_array($item->id, $commonTeams);
        });
        $tiers = $this->tierService->getTPLTiers();

        return inertia('Dashboard/TPLConversion', [
            'tplDashboardStats' => $tplDashboardStats,
            'teams' => $teams,
            'tiers' => $tiers,
        ]);
    }

    public function getTPLDashboardStats(Request $request): array
    {
        $tiers = Tier::where('can_handle_tpl', 1)->where('is_active', 1)->get()->pluck('id');
        $tplTeam = Team::where('name', 'TPL')->where('is_active', 1)->first();
        $records = CarQuote::query()
            ->select(
                'users.id as advisorId',
                DB::raw('DATE_FORMAT(quote_batches.start_date, "%d-%m-%Y") as start_date'),
                DB::raw('DATE_FORMAT(quote_batches.end_date, "%d-%m-%Y") as end_date'),
                'quote_batches.name as batch_name',
                'users.name as advisor_name',
                'quote_batches.id as quote_batch_id',
                DB::raw('SUM(CASE WHEN car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as total_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as new_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as not_interested'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.','.QuoteStatusEnum::PendingQuote.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.'))  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'"  THEN 1 ELSE 0 END) as created_sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::IMRenewal.' THEN 1 ELSE 0 END)  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" as afia_renewals_count'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
            )
            ->filterBySegment()
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO])
            ->where('users.is_active', true)
            ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
            ->orderByDesc('quote_batches.start_date')->orderBy('users.email');

        if (isset($request->tier_filter) && $request->tier_filter != 'undefined') {
            $records->whereIn('tiers.id', $request->tier_filter);
        } else {
            $records->whereIn('tiers.id', $tiers);
        }

        if (isset($request->team_filter) && $request->team_filter != 'undefined') {
            $records->whereIn('users.id', function ($query) use ($request) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $request->team_filter);
            });
        } elseif ($tplTeam != null) {
            $records->whereIn('users.id', function ($query) use ($tplTeam) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->where('teams.id', $tplTeam->id);
            });
        }

        if (isset($request->userFilter) && $request->userFilter != 'null') {
            $records = $this->applyFilter($records, 'car_quote_request.advisor_id', $request->userFilter, gettype($request->userFilter) == 'array' ? IMCRMSearchTypesEnum::MULTI_SEARCH : IMCRMSearchTypesEnum::EQUAL_SEARCH);
        }

        if (isset($request->isCommercial) && $request->isCommercial != 'All') {
            $commecialValue = $request->isCommercial == 'true' ? true : false;
            $records->where('car_model.is_commercial', '=', $commecialValue);
        }

        $labels = [];
        $data = [];
        $records = $records->get();

        $batchesWiseGroupedData = $records->groupBy('batch_name')->take(10)->sortKeys()->toArray();

        foreach ($batchesWiseGroupedData as $batchData) {
            $saleLeads = 0;
            $createdSaleLeads = 0;
            $totalLeads = 0;
            $badLeads = 0;
            $manualCreatedBadLeads = 0;
            foreach ($batchData as $record) {
                $saleLeads = $saleLeads + $record['sale_leads'];
                $createdSaleLeads = $createdSaleLeads + $record['created_sale_leads'];
                $totalLeads = $totalLeads + $record['total_leads'];
                $badLeads = $badLeads + $record['bad_leads'];
                $manualCreatedBadLeads = $manualCreatedBadLeads + $record['manual_created_bad_leads'];
            }
            $numerator = $saleLeads;
            $denominator = $totalLeads - $badLeads;
            $total = $denominator > 0 ? ($numerator / $denominator) : 0;

            $data[] = number_format((float) $total * 100, 2, '.', '');
            $labels[] = $record['batch_name'].'-('.$record['start_date'].' to '.$record['end_date'].')';
        }

        return isset($request->tier_filter) || isset($request->source) ? [json_encode($labels, JSON_OBJECT_AS_ARRAY), json_encode($data, JSON_OBJECT_AS_ARRAY)] : [$labels, $data];
    }

    private function applyFilter($query, $column, $value, $searchType)
    {
        switch ($searchType) {
            case IMCRMSearchTypesEnum::EQUAL_SEARCH:
                $query = $query->where($column, $value);
                break;
            case IMCRMSearchTypesEnum::LIKE_SEARCH:
                $query = $query->where($column, 'like', '%'.$value.'%');
                break;
            case IMCRMSearchTypesEnum::MULTI_SEARCH:
                $query = $query->whereIn($column, $value);
                break;
            case IMCRMSearchTypesEnum::NOT_EQUAL:
                $query = $query->where($column, '!=', $value);
                break;
            case IMCRMSearchTypesEnum::NOT_NULL:
                $query = $query->whereNotNull($column);
                break;
            case IMCRMSearchTypesEnum::NULL:
                $query = $query->whereNull($column);
                break;
            default:
                break;
        }

        return $query;
    }

    public function getComprehensiveDashboardStats(Request $request): array
    {
        $compTiers = Tier::where('can_handle_tpl', 0)->orderBy('name', 'asc')->where('name', '!=', TiersEnum::TIER_R)->where('is_active', 1)->get()->pluck('id');
        $records = CarQuote::query()
            ->select(
                'users.id as advisorId',
                DB::raw('DATE_FORMAT(quote_batches.start_date, "%d-%m-%Y") as start_date'),
                DB::raw('DATE_FORMAT(quote_batches.end_date, "%d-%m-%Y") as end_date'),
                'quote_batches.name as batch_name',
                'users.name as advisor_name',
                'quote_batches.id as quote_batch_id',
                DB::raw('SUM(CASE WHEN car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as total_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as new_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as not_interested'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.','.QuoteStatusEnum::PendingQuote.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.'))  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'"  THEN 1 ELSE 0 END) as created_sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::IMRenewal.' THEN 1 ELSE 0 END)  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" as afia_renewals_count'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
            )
            ->filterBySegment()
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO])
            ->where('users.is_active', true)
            ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
            ->orderByDesc('quote_batches.start_date')->orderBy('users.email');

        if (
            ! auth()->user()->hasAnyRole([
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ]) && auth()->user()->isManagerORDeputy()
        ) {
            $userIds = $this->walkTree(auth()->user()->id, quoteTypeCode::Car);
            $userIds = UserManager::where('manager_id', auth()->user()->id)
                ->get()
                ->filter(function ($user) use ($userIds) {
                    return in_array($user->user_id, $userIds);
                })
                ->pluck('user_id')
                ->toArray();
            $records = $records->whereIn('car_quote_request.advisor_id', $userIds);
        }

        if (isset($request->tiers) && $request->tiers != 'undefined') {
            $records->whereIn('tiers.id', $request->tiers);
        } else {
            $records->whereIn('tiers.id', $compTiers);
        }

        if (isset($request->teams) && $request->teams != 'undefined') {
            $records->whereIn('users.id', function ($query) use ($request) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $request->teams);
            });

            if (isset($request->sub_teams) && $request->sub_teams != 'undefined') {
                $records->whereIn('users.sub_team_id', $request->sub_teams);
            }
        } else {
            $organicTeam = Team::where('name', 'Organic')->first();
            $records->whereIn('users.id', function ($query) use ($organicTeam) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->where('teams.id', $organicTeam->id);
            });
        }

        if (isset($request->advisors) && $request->advisors != 'null') {
            $records = $this->applyFilter($records, 'car_quote_request.advisor_id', $request->advisors, gettype($request->advisors) == 'array' ? IMCRMSearchTypesEnum::MULTI_SEARCH : IMCRMSearchTypesEnum::EQUAL_SEARCH);
        }

        if (isset($request->isCommercial) && $request->isCommercial != 'All') {
            $commecialValue = $request->isCommercial == 'true' ? true : false;
            $records->where('car_model.is_commercial', '=', $commecialValue);
        }

        if (isset($request->segment_filter) && $request->segment_filter != 'all') {
            $records = $records->filterBySegment($request->segment_filter, QuoteTypeId::Car);
        }

        $labels = [];
        $data = [];
        $records = $records->get();

        $batchesWiseGroupedData = $records->groupBy('batch_name')->take(10)->sortKeys()->toArray();

        foreach ($batchesWiseGroupedData as $batchData) {
            $saleLeads = 0;
            $createdSaleLeads = 0;
            $totalLeads = 0;
            $badLeads = 0;
            $manualCreatedBadLeads = 0;
            foreach ($batchData as $record) {
                $saleLeads = $saleLeads + $record['sale_leads'];
                $createdSaleLeads = $createdSaleLeads + $record['created_sale_leads'];
                $totalLeads = $totalLeads + $record['total_leads'];
                $badLeads = $badLeads + $record['bad_leads'];
                $manualCreatedBadLeads = $manualCreatedBadLeads + $record['manual_created_bad_leads'];
            }
            $numerator = $saleLeads;
            $denominator = $totalLeads - $badLeads;
            $total = $denominator > 0 ? ($numerator / $denominator) : 0;

            $data[] = number_format((float) $total * 100, 2, '.', '');
            $labels[] = $record['batch_name'].'-('.$record['start_date'].' to '.$record['end_date'].')';
        }

        if (empty($data)) {
            $data[] = ['0.00'];
            $labels = [' '];
        }

        return isset($request->tier_filter) || isset($request->userFilter) ? [json_encode($labels, JSON_OBJECT_AS_ARRAY), json_encode($data, JSON_OBJECT_AS_ARRAY)] : [$labels, $data];
    }

    public function getCommonTeamsForCurrentUserWithCar()
    {
        $userId = auth()->user()->id;
        $userTeams = $this->getUserTeams($userId)->pluck('id')->toArray();
        info('Inside getCommonTeamsForCurrentUserWithCar user teams are : '.json_encode($userTeams));
        $teamsByProduct = $this->getTeamsByProductName(quoteTypeCode::Car)->pluck('id')->toArray();
        info('Inside getCommonTeamsForCurrentUserWithCar teams by product are : '.json_encode($teamsByProduct));

        return (count($userTeams) > 0 && count($teamsByProduct) > 0) ? array_intersect($userTeams, $teamsByProduct) : [];
    }

    public function renderComprehensiveDashboard(Request $request, ComprehensiveConversionDashboardService $comprehensiveConversionDashboardService)
    {
        $lob = $request->lob ?? quoteTypeCode::Car;
        if ($lob === quoteTypeCode::Car) {
            $comprehensiveDashboardStats = $this->getComprehensiveDashboardStats($request);
        } else {
            $comprehensiveDashboardStats = $comprehensiveConversionDashboardService->getReportData($request);
        }

        info('inside renderComprehensiveDashboard comp stats are : '.json_encode($comprehensiveDashboardStats));

        return inertia('Dashboard/ComperhensiveConversion', [
            'reportData' => $comprehensiveDashboardStats,
            'filtersByLob' => $comprehensiveConversionDashboardService->getFiltersByLob(),
            'filterOptions' => $comprehensiveConversionDashboardService->getFilterOptions(),
            'defaultFilters' => $comprehensiveConversionDashboardService->getDefaultFilters(),
        ]);
    }

    public function conversionStats($quoteType)
    {
        $statsArray = $this->getWeeklyStats($quoteType);
        $headingArray = $this->getWeeklyHeading();

        return inertia('Dashboard/'.ucwords($quoteType).'Conversion', [
            'statsArray' => $statsArray,
            'headingArray' => $headingArray,
            'qouteType' => $quoteType,
        ]);
    }

    public function getWeeklyStats($type): array
    {
        return [
            '1Week' => $this->dashboardService->getDashboardStatsByDate(
                $this->dashboardService->getPastDateByWeek(0, true),
                $this->dashboardService->getPastDateByWeek(0, false),
                $type
            ),
            '2Week' => $this->dashboardService->getDashboardStatsByDate(
                $this->dashboardService->getPastDateByWeek(1, true),
                $this->dashboardService->getPastDateByWeek(1, false),
                $type
            ),
            '3Week' => $this->dashboardService->getDashboardStatsByDate(
                $this->dashboardService->getPastDateByWeek(2, true),
                $this->dashboardService->getPastDateByWeek(2, false),
                $type
            ),
            '4Week' => $this->dashboardService->getDashboardStatsByDate(
                $this->dashboardService->getPastDateByWeek(3, true),
                $this->dashboardService->getPastDateByWeek(3, false),
                $type
            ),
        ];
    }

    public function getWeeklyHeading(): array
    {
        return [
            '1Week' => $this->dashboardService->getWeekHeadingDate(0),
            '2Week' => $this->dashboardService->getWeekHeadingDate(1),
            '3Week' => $this->dashboardService->getWeekHeadingDate(2),
            '4Week' => $this->dashboardService->getWeekHeadingDate(3),
        ];
    }

    public function getTeamAdvisorConversionStats(Request $request)
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $startDate = null;
        $endDate = null;
        if (isset($request->range)) {
            $startDate = Carbon::parse(explode(',', $request->range)[0])->startOfDay()->format($dateFormat);
            $endDate = Carbon::parse(explode(',', $request->range)[1])->endOfDay()->format($dateFormat);
        } else {
            $startDate = now()->startOfDay()->format($dateFormat);
            $endDate = now()->endOfDay()->format($dateFormat);
        }
        $filters = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'teamIds' => $request->teamFilter,
        ];

        return $this->dashboardService->getAdvisorLeadAssignedData($filters);
    }

    public function getUsersByTeam(Request $request)
    {
        return $this->getUsersByTeamId($request->team_filter);
    }

    public function getSubTeamsByTeam(Request $request)
    {
        return $this->getSubTeamsByTeamIds($request->team_filter);
    }

    public function getUsersBySubTeam(Request $request)
    {
        return $this->getUsersBySubTeamIds($request->sub_team_filter);
    }

    public function getTeamsByProduct(Request $request)
    {
        $productName = QuoteTypes::getName($request->quote_type_id);

        $product = $this->getProductByName($productName);

        $teams = $this->getTeamsByProductId($product->id);

        return response()->json(['teams' => $teams], 200);
    }
}
