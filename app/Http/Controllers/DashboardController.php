<?php

namespace App\Http\Controllers;

use App\Enums\IMCRMSearchTypesEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\TiersEnum;
use App\Models\CarQuote;
use App\Models\QuoteBatches;
use App\Models\Tier;
use App\Services\DashboardService;
use App\Traits\TeamHierarchyHelpers;
use DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use TeamHierarchyHelpers;

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
        $todaysLeads = CarQuote::whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->get();
        $car = $this->getProductByName(quoteTypeCode::Car);
        $teams = $this->getTeamsByProductId($car->id);
        $carAdvisors = $this->getUsersByTeamId($car->id);
        $teamWiseLeadsAssignedAverage = $this->dashboardService->getTeamWiseLeadStats($todaysLeads, $teams);

        $totalLeadsReceived = count($todaysLeads);
        $totalLeadsReceivedEcommerce = count($todaysLeads->where('is_ecommerce', 1));
        $totalUnAssignedLeadsReceived = count($todaysLeads->whereNull('advisor_id'));
        $totalUnAssignedLeadsReceivedEcommerce = count($todaysLeads->whereNull('advisor_id')->where('is_ecommerce', 1));
        $totalUnAssignedRevivalLeads = count($todaysLeads->whereNull('advisor_id')->where('source', LeadSourceEnum::REVIVAL));

        $leadsCountByTier = $this->dashboardService->getLeadsCountByTier(null, null);
        $unAssignedLeadsByTier = $this->dashboardService->getUnAssignedLeadsCountByTier($request);
        $revivalLeadsCount = $this->dashboardService->getLeadsCountRevival(null, null);
        $assignedLeadsBySource = $this->dashboardService->getAssignedLeadsCountBySource(null, null);
        $advisorConversionData = $this->dashboardService->getAdvisorConversionData(null);
        $advisorLeadsAssignedData = $this->dashboardService->getAdvisorLeadAssignedData(null);

        return view('dashboard.main_dashboard', compact(['totalLeadsReceived', 'totalLeadsReceivedEcommerce', 'totalUnAssignedLeadsReceived', 'totalUnAssignedLeadsReceivedEcommerce',
            'teams', 'carAdvisors', 'teamWiseLeadsAssignedAverage', 'totalUnAssignedRevivalLeads', 'leadsCountByTier', 'unAssignedLeadsByTier',
            'revivalLeadsCount', 'advisorConversionData', 'advisorLeadsAssignedData', 'assignedLeadsBySource', ]));
    }

    public function getRecentDailyStats(Request $request)
    {
        $startDate = explode(',', $request->range)[0];
        $endDate = explode(',', $request->range)[1];
        $allCarQuotesToday = CarQuote::whereBetween('created_at', [$startDate, $endDate])->get();
        $car = $this->getProductByName(quoteTypeCode::Car);
        $teams = $this->getTeamsByProductId($car->id);
        foreach ($teams as $team) {
            $teamUserIds = $this->getUsersByTeamId($team->id)->pluck('id');
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

    public function renderTplDashboard(Request $request)
    {
        $tplDashboardStats = $this->getTPLDashboardStats($request);
        $car = $this->getProductByName(quoteTypeCode::Car);
        $teams = $this->getTeamsByProductId($car->id);

        return view('dashboard.tpl_dashboard', compact('tplDashboardStats', 'teams'));
    }

    public function getTPLDashboardStats(Request $request): array
    {
        $tiers = Tier::whereIn('name', [TiersEnum::TierTR, TiersEnum::Tier6])->get()->pluck('id');
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
        ->join('user_team', 'user_team.user_id', 'car_quote_request.advisor_id')
        ->join('teams', 'teams.id', 'user_team.team_id')
        ->groupBy('quote_batches.name', 'quote_batches.id')->take(10)->orderBy('quote_batches.start_date', 'desc');

        $isTierDefined = isset($request->tier_filter) && $request->tier_filter != 'undefined';
        $records = $this->applyFilter($records, 'tiers.id', $isTierDefined ? $request->tier_filter : $tiers, $isTierDefined ? IMCRMSearchTypesEnum::EQUAL_SEARCH : IMCRMSearchTypesEnum::MULTI_SEARCH);

        if (isset($request->team_filter) && $request->team_filter != 'undefined') {
            $records = $this->applyFilter($records, 'teams.id', $request->team_filter, IMCRMSearchTypesEnum::MULTI_SEARCH);
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
        ->join('user_team', 'user_team.user_id', 'car_quote_request.advisor_id')
        ->join('teams', 'teams.id', 'user_team.team_id')
        ->groupBy('quote_batches.name', 'quote_batches.id')->skip(0)->take(10)->orderBy('quote_batches.id', 'desc');
        $isTierDefined = isset($request->tier_filter) && $request->tier_filter != 'null';
        $records = $this->applyFilter($records, 'tiers.id', $isTierDefined ? $request->tier_filter : $compTiers, $isTierDefined ? IMCRMSearchTypesEnum::EQUAL_SEARCH : IMCRMSearchTypesEnum::MULTI_SEARCH);

        if (isset($request->team_filter) && $request->team_filter != 'undefined') {
            $records = $this->applyFilter($records, 'teams.id', $request->team_filter, IMCRMSearchTypesEnum::MULTI_SEARCH);
        }

        if (isset($request->userFilter) && $request->userFilter != 'null') {
            $records = $this->applyFilter($records, 'car_quote_request.advisor_id', $request->userFilter, IMCRMSearchTypesEnum::MULTI_SEARCH);
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
        $carUsers = $this->getUsersByProductName(quoteTypeCode::Car);
        $tiers = Tier::whereNotIn('name', [TiersEnum::TierTR, TiersEnum::Tier6])->orderBy('name', 'asc')->get();
        $comprehensiveDashboardStats = $this->getComprehensiveDashboardStats($request);
        $teams = $this->getTeamsByProductName(quoteTypeCode::Car);

        return view('dashboard.comprehensive_dashboard', compact('carUsers', 'tiers', 'comprehensiveDashboardStats', 'teams'));
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

    public function getTeamAdvisorConversionStats(Request $request)
    {
        return $this->dashboardService->getAdvisorLeadAssignedData($request->teamFilter);
    }

    public function getAdvisorConversionStats(Request $request)
    {
        return $this->dashboardService->getAdvisorConversionData($request->advisorFilter);
    }
}
