<?php

namespace App\Http\Controllers;

use App\Enums\IMCRMSearchTypesEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\TiersEnum;
use App\Models\CarQuote;
use App\Models\QuoteBatches;
use App\Models\Team;
use App\Models\Tier;
use App\Models\User;
use App\Services\DashboardService;
use DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
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

    public function renderMainDashboard(Request $request)
    {
        $allCarQuotesToday = CarQuote::whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->get();
        $carTeam = Team::where('name', quoteTypeCode::Car)->first();
        $teams = Team::where('parent_team_id', $carTeam->id)->get();
        $carAdvisors = User::where('sub_team_id', $teams->pluck('id')->toArray())->get();
        foreach ($teams as $team) {
            $teamUserIds = $carAdvisors->where('sub_team_id', $team->id)->pluck('id');
            $teamWiseLeadsAssignedAverage[] = [
                'totalUsersUnderTeam' => count($teamUserIds),
                'teamName' => $team->name,
                'totalLeadsCount' => $allCarQuotesToday->whereIn('advisor_id', $teamUserIds)->count(),
            ];
        }
        $totalLeadsReceived = count($allCarQuotesToday);
        $totalLeadsReceivedEcommerce = count($allCarQuotesToday->where('is_ecommerce', 1));
        $totalUnAssignedLeadsReceived = count($allCarQuotesToday->whereNull('advisor_id'));
        $totalUnAssignedLeadsReceivedEcommerce = count($allCarQuotesToday->whereNull('advisor_id')->where('is_ecommerce', 1));
        $totalUnAssignedRevivalLeads = count($allCarQuotesToday->whereNull('advisor_id')->where('source', LeadSourceEnum::REVIVAL));

        $leadsCountByTier = $this->getLeadsCountByTier(null, null);
        $unAssignedLeadsByTier = $this->getUnAssignedLeadsCountByTier($request);
        $revivalLeadsCount = $this->getLeadsCountRevival(null, null);
        $assignedLeadsBySource = $this->getAssignedLeadsCountBySource(null, null);
        $advisorConversionData = $this->getAdvisorConversionData(null);
        $advisorLeadsAssignedData = $this->getAdvisorLeadAssignedData(null);

        return view('dashboard.main_dashboard', compact(['totalLeadsReceived', 'totalLeadsReceivedEcommerce', 'totalUnAssignedLeadsReceived', 'totalUnAssignedLeadsReceivedEcommerce',
            'teams', 'carAdvisors', 'teamWiseLeadsAssignedAverage', 'totalUnAssignedRevivalLeads', 'leadsCountByTier', 'unAssignedLeadsByTier',
            'revivalLeadsCount', 'advisorConversionData', 'advisorLeadsAssignedData', 'assignedLeadsBySource', ]));
    }

    public function getAssignedLeadsCountBySource($startDate, $endDate)
    {
        $query = CarQuote::select(
            DB::raw('distinct(source) as sourceName'),
            DB::raw('count(*) as sourceCount'),
        );
        if ($startDate == null && $endDate == null) {
            $query->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()]);
        } else {
            $query->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);
        }

        return $query->get();
    }

    public function getRecentDailyStats(Request $request)
    {
        $startDate = explode(',', $request->range)[0];
        $endDate = explode(',', $request->range)[1];
        $allCarQuotesToday = CarQuote::whereBetween('created_at', [$startDate, $endDate])->get();
        $carTeam = Team::where('name', quoteTypeCode::Car)->first();
        $teams = Team::where('parent_team_id', $carTeam->id)->get();
        foreach ($teams as $team) {
            $teamUserIds = User::where('sub_team_id', $team->id)->pluck('id');
            $teamWiseLeadsAssignedAverage[] = [
                'totalUsersUnderTeam' => count($teamUserIds),
                'teamName' => $team->name,
                'totalLeadsCount' => CarQuote::whereIn('advisor_id', $teamUserIds)->whereBetween('created_at', [$startDate, $endDate])->count(),
            ];
        }
        $totalLeadsReceived = count($allCarQuotesToday);
        $totalLeadsReceivedEcommerce = count($allCarQuotesToday->where('is_ecommerce', 1));
        $totalUnAssignedLeadsReceived = count($allCarQuotesToday->whereNull('advisor_id'));
        $totalUnAssignedLeadsReceivedEcommerce = count($allCarQuotesToday->whereNull('advisor_id')->where('is_ecommerce', 1));
        $totalUnAssignedRevivalLeads = count($allCarQuotesToday->whereNull('advisor_id')->where('source', LeadSourceEnum::REVIVAL));
        $leadsCountByTier = $this->getLeadsCountByTier($startDate, $endDate);
        $revivalLeadsCount = $this->getLeadsCountRevival($startDate, $endDate);

        return ['totalLeadsReceived' => $totalLeadsReceived, 'totalLeadsReceivedEcommerce' => $totalLeadsReceivedEcommerce, 'totalUnAssignedLeadsReceived' => $totalUnAssignedLeadsReceived,
            'totalUnAssignedLeadsReceivedEcommerce' => $totalUnAssignedLeadsReceivedEcommerce, 'teamWiseLeadsAssignedAverage' => $teamWiseLeadsAssignedAverage,
            'totalUnAssignedRevivalLeads' => $totalUnAssignedRevivalLeads, 'leadsCountByTier' => $leadsCountByTier, 'revivalLeadsCount' => $revivalLeadsCount, ];
    }

    public function getLeadsCountRevival($startDate, $endDate)
    {
        $query = CarQuote::select(
            DB::raw('sum(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::REVIVAL.'" THEN 1 ELSE 0 END) as revival_leads'),
            DB::raw('sum(CASE WHEN car_quote_request.source != "'.LeadSourceEnum::REVIVAL.'" THEN 1 ELSE 0 END) as non_revival_leads'),
        );
        if ($startDate == null && $endDate == null) {
            $query->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()]);
        } else {
            $query->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);
        }

        return $query->get();
    }

    public function getLeadsCountByTier($startDate, $endDate)
    {
        $query = CarQuote::select(
            'tiers.name as tierNames',
            DB::raw('count(*) as leadCount')
        )
        ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
        ->groupBy('tiers.name');
        if ($startDate == null && $endDate == null) {
            $query->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()]);
        } else {
            $query->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);
        }

        return $query->get();
    }

    public function getUnAssignedLeadsCountByTier($request)
    {
        return
        CarQuote::select(
            'tiers.name as tierNames',
            DB::raw('count(*) as leadCount')
        )
        ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
        ->whereNull('car_quote_request.advisor_id')
        ->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()])
        ->groupBy('tiers.name')
        ->get();
    }

    public function getAdvisorLeadAssignedData($teamIds)
    {
        $query = CarQuote::select(
            'users.name',
            DB::raw('COUNT(car_quote_request.id) AS total_leads'),
        )
        ->join('users', 'users.id', 'car_quote_request.advisor_id')
        ->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()])
        ->groupBy('users.name');
        if (isset($teamIds)) {
            $userIds = User::where(function ($query) use ($teamIds) {
                $query->whereIn('team_id', [$teamIds])
                ->orWhere('sub_team_id', [$teamIds]);
            })->pluck('id');
            $query->whereIn('users.id', $userIds);
        }

        return $query->get();
    }

    public function getTeamAdvisorConversionStats(Request $request)
    {
        return $this->getAdvisorLeadAssignedData($request->teamFilter);
    }

    public function getAdvisorConversionData($advisorId)
    {
        $query = CarQuote::select(
            'quote_batches.name',
            'quote_batches.start_date',
            'quote_batches.end_date',
            DB::raw('COUNT(car_quote_request.id) AS total_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM" THEN 1 ELSE 0 END) AS manual_created'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id IN (9, 35) THEN 1 ELSE 0 END) AS bad_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 33 THEN 1 ELSE 0 END) AS sale_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM"
            AND car_quote_request.quote_status_id = 15 THEN 1 ELSE 0 END) AS created_sale_leads'),
        )
        ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
        ->groupBy('quote_batches.name')
        ->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()])
        ->orderBy('quote_batches.id', 'desc')
        ->take(10);
        if (isset($advisorId)) {
            $query->where('car_quote_request.advisor_id', $advisorId);
        }

        return $query->get();
    }

    public function getAdvisorConversionStats(Request $request)
    {
        return $this->getAdvisorConversionData($request->advisorFilter);
    }

    public function renderTplDashboard(Request $request)
    {
        $tplDashboardStats = $this->getTPLDashboardStats($request);

        return view('dashboard.tpl_dashboard', compact('tplDashboardStats'));
    }

    public function getTPLDashboardStats(Request $request): array
    {
        $tiers = Tier::whereIn('name', [TiersEnum::TierTR, TiersEnum::Tier6])->get();
        $tplTiers = $tiers->pluck('id');
        $records = QuoteBatches::query()
        ->select(
            'quote_batches.id',
            'quote_batches.name',
            'quote_batches.start_date',
            'quote_batches.end_date',
            DB::raw('count(car_quote_request.id) as total_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (9,35) THEN 1 ELSE 0 END) as bad_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 33 THEN 1 ELSE 0 END) as sale_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" and car_quote_request.quote_status_id = 15 THEN 1 ELSE 0 END) as created_sale_leads'),
        )
        ->leftJoin('car_quote_request', 'quote_batches.id', 'car_quote_request.quote_batch_id')
        ->leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
        ->groupBy('quote_batches.name', 'quote_batches.id')->take(10)->orderBy('quote_batches.start_date', 'desc');

        if (isset($request->tier_filter)) {
            $records = $this->applyFilter($records, 'tiers.id', $request->tier_filter, IMCRMSearchTypesEnum::EQUAL_SEARCH);
        }
        if ($request->tier_filter == '') {
            $records = $this->applyFilter($records, 'tiers.id', $tplTiers, IMCRMSearchTypesEnum::MULTI_SEARCH);
        }
        if (isset($request->source)) {
            if ($request->source == 'no') {
                $records = $this->applyFilter($records, 'car_quote_request.source', LeadSourceEnum::IMCRM, IMCRMSearchTypesEnum::EQUAL_SEARCH);
            }
            if ($request->source == 'yes') {
                $records = $this->applyFilter($records, 'car_quote_request.source', LeadSourceEnum::IMCRM, IMCRMSearchTypesEnum::NOT_EQUAL);
            }
        }
        $labels = [];
        $data = [];
        foreach ($records->get() as $record) {
            $percentage = (($record->sale_leads - $record->created_sale_leads) / (($record->total_leads - $record->bad_leads - $record->manual_created) > 0 ? ($record->total_leads - $record->bad_leads - $record->manual_created) : 1));
            $data[] = number_format((float) $percentage, 2, '.', '');
            $labels[] = $record->name.'-('.$record->start_date.' to '.$record->end_date.')';
        }

        return isset($request->tier_filter) || isset($request->source) ? [json_encode($labels, JSON_OBJECT_AS_ARRAY), json_encode($data, JSON_OBJECT_AS_ARRAY)] : [$labels, $data];
    }

    private function applyFilter($query, $column, $value, $searchType)
    {
        switch($searchType) {
            case IMCRMSearchTypesEnum::EQUAL_SEARCH :
                $query = $query->where($column, $value);
                break;
            case IMCRMSearchTypesEnum::LIKE_SEARCH :
                $query = $query->where($column, 'like', '%'.$value.'%');
                break;
            case IMCRMSearchTypesEnum::MULTI_SEARCH :
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
        $tiers = Tier::whereNotIn('name', [TiersEnum::TierTR, TiersEnum::Tier6])->get();
        $compTiers = $tiers->pluck('id');
        $records = QuoteBatches::query()
        ->select(
            'quote_batches.name',
            'quote_batches.start_date',
            'quote_batches.end_date',
            DB::raw('count(car_quote_request.id) as total_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (9,35) THEN 1 ELSE 0 END) as bad_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 33 THEN 1 ELSE 0 END) as sale_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" and car_quote_request.quote_status_id = 15 THEN 1 ELSE 0 END) as created_sale_leads'),
        )
        ->leftJoin('car_quote_request', 'quote_batches.id', 'car_quote_request.quote_batch_id')
        ->leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
        ->groupBy('quote_batches.name', 'quote_batches.id')->skip(0)->take(10)->orderBy('quote_batches.id', 'desc');
        if ($request->tier_filter == '') {
            $records = $this->applyFilter($records, 'tiers.id', $compTiers, IMCRMSearchTypesEnum::MULTI_SEARCH);
        }
        if (isset($request->tier_filter)) {
            $records = $this->applyFilter($records, 'tiers.id', [$request->tier_filter], IMCRMSearchTypesEnum::MULTI_SEARCH);
        }
        if (isset($request->userFilter)) {
            $records = $this->applyFilter($records, 'car_quote_request.advisor_id', [$request->userFilter], IMCRMSearchTypesEnum::MULTI_SEARCH);
        }
        $labels = [];
        $data = [];
        foreach ($records->get() as $record) {
            $percentage = (($record->sale_leads - $record->created_sale_leads) / (($record->total_leads - $record->bad_leads - $record->manual_created) > 0 ? ($record->total_leads - $record->bad_leads - $record->manual_created) : 1));
            $data[] = number_format((float) $percentage, 2, '.', '');
            $labels[] = $record->name.'-('.$record->start_date.' to '.$record->end_date.')';
        }

        return isset($request->tier_filter) || isset($request->userFilter) ? [json_encode($labels, JSON_OBJECT_AS_ARRAY), json_encode($data, JSON_OBJECT_AS_ARRAY)] : [$labels, $data];
    }

    public function renderComprehensiveDashboard(Request $request)
    {
        $carTeam = Team::where('name', quoteTypeCode::Car)->first();
        $carUsers = User::where('team_id', $carTeam->id)->orderBy('name', 'asc')->get();
        $tiers = Tier::whereNotIn('name', [TiersEnum::TierTR, TiersEnum::Tier6])->orderBy('name', 'asc')->get();
        $comprehensiveDashboardStats = $this->getComprehensiveDashboardStats($request);

        return view('dashboard.comprehensive_dashboard', compact('carUsers', 'tiers', 'comprehensiveDashboardStats'));
    }

    public function conversionStats($quoteType)
    {
        $statsArray = $this->getWeeklyStats($quoteType);
        $headingArray = $this->getWeeklyHeading();

        return view('dashboard.'.$quoteType.'-conversion', compact('statsArray', 'headingArray'));
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
            '1WeekHeadingDate' => $this->dashboardService->getWeekHeadingDate(0),
            '2WeekHeadingDate' => $this->dashboardService->getWeekHeadingDate(1),
            '3WeekHeadingDate' => $this->dashboardService->getWeekHeadingDate(2),
            '4WeekHeadingDate' => $this->dashboardService->getWeekHeadingDate(3),
        ];
    }
}
