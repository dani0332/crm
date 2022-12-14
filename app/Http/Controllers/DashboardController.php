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
        $allCarQuotesToday = CarQuote::whereBetween('created_at', [now()->addDays(-90)->startOfDay(), now()->endOfDay()])->get();
        $carTeam = Team::where('name', quoteTypeCode::Car)->first();
        $teams = Team::where('parent_team_id', $carTeam->id)->get();
        $carAdvisors = User::where(function ($query) use ($carTeam, $teams) {
            $query->where('team_id', $carTeam->id)
            ->orWhere('sub_team_id', $teams->pluck('id')->toArray());
        })->get();
        foreach ($teams as $team) {
            $teamUserIds = User::where('sub_team_id', $team->id)->pluck('id');
            $teamWiseLeadsAssignedAverage[] = [
                'totalUsersUnderTeam' => count($teamUserIds),
                'teamName' => $team->name,
                'totalLeadsCount' => CarQuote::whereIn('advisor_id', $teamUserIds)->whereBetween('created_at', [now()->addDays(-90)->startOfDay(), now()->endOfDay()])->count(),
            ];
        }
        $totalLeadsReceived = count($allCarQuotesToday);
        $totalLeadsReceivedEcommerce = count($allCarQuotesToday->where('is_ecommerce', 1));
        $totalUnAssignedLeadsReceived = count($allCarQuotesToday->whereNull('advisor_id'));
        $totalUnAssignedLeadsReceivedEcommerce = count($allCarQuotesToday->whereNull('advisor_id')->where('is_ecommerce', 1));
        $totalUnAssignedRevivalLeads = count($allCarQuotesToday->whereNull('advisor_id')->where('source', LeadSourceEnum::REVIVAL));
        $leadsCountByTier = $this->getLeadsCountByTier($request);
        $unAssignedLeadsByTier = $this->getUnAssignedLeadsCountByTier($request);
        $revivalLeadsCount = $this->getLeadsCountRevival($request);
        $advisorConversionData = $this->getAdvisorConversionData($request);
        $advisorLeadsAssignedData = $this->getAdvisorLeadAssignedData($request);

        return view('dashboard.main_dashboard', compact(['totalLeadsReceived', 'totalLeadsReceivedEcommerce', 'totalUnAssignedLeadsReceived', 'totalUnAssignedLeadsReceivedEcommerce',
            'teams', 'carAdvisors', 'teamWiseLeadsAssignedAverage', 'totalUnAssignedRevivalLeads', 'leadsCountByTier', 'unAssignedLeadsByTier', 'revivalLeadsCount', 'advisorConversionData', 'advisorLeadsAssignedData', ]));
    }

    public function getLeadsCountRevival($request)
    {
        return
        CarQuote::select(
            DB::raw('sum(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::REVIVAL.'" THEN 1 ELSE 0 END) as revival_leads'),
            DB::raw('sum(CASE WHEN car_quote_request.source != "'.LeadSourceEnum::REVIVAL.'" THEN 1 ELSE 0 END) as non_revival_leads'),
        )
        ->whereBetween('car_quote_request.created_at', [now()->addDays(-90)->startOfDay(), now()->endOfDay()])
        ->get();
    }

    public function getLeadsCountByTier($request)
    {
        return
        CarQuote::select(
            'tiers.name as tierNames',
            DB::raw('count(*) as leadCount')
        )
        ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
        ->whereBetween('car_quote_request.created_at', [now()->addDays(-90)->startOfDay(), now()->endOfDay()])
        ->groupBy('tiers.name')
        ->get();
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
        ->whereBetween('car_quote_request.created_at', [now()->addDays(-90)->startOfDay(), now()->endOfDay()])
        ->groupBy('tiers.name')
        ->get();
    }

    public function getAdvisorLeadAssignedData($request)
    {
        return
        CarQuote::select(
            'users.name',
            DB::raw('COUNT(car_quote_request.id) AS total_leads'),
        )
        ->join('users', 'users.id', 'car_quote_request.advisor_id')
        ->whereBetween('car_quote_request.created_at', [now()->addDays(-90)->startOfDay(), now()->endOfDay()])
        ->groupBy('users.name')
        ->get();
    }

    public function getAdvisorConversionData($request)
    {
        return
        CarQuote::select(
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
        ->orderBy('quote_batches.id', 'desc')
        ->take(10)
        ->get();
    }

    public function renderTplDashboard(Request $request)
    {
        $stats = $this->getTPLDashboardStats($request);

        return view('dashboard.tpl_dashboard', compact('labels', 'data'));
    }

    public function getTPLDashboardStats(Request $request): array
    {
        $tiers = Tier::whereIn('name', [TiersEnum::TierTR, TiersEnum::Tier6])->get();
        $tplTiers = $tiers->pluck('id');
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
            $labels[] = $record->name;
        }

        return [$labels, $data];
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
                $query = $query->whereNotIn($column, '!=', $value);
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
        if (isset($request->tier_filter)) {
            $records = $this->applyFilter($records, 'tiers.id', $request->tier_filter, IMCRMSearchTypesEnum::EQUAL_SEARCH);
        }
        if ($request->tier_filter == '') {
            $records = $this->applyFilter($records, 'tiers.id', $compTiers, IMCRMSearchTypesEnum::MULTI_SEARCH);
        }
        if (isset($request->userFilter)) {
            $records = $this->applyFilter($records, 'car_quote_request.advisor_id', $request->userFilter, IMCRMSearchTypesEnum::EQUAL_SEARCH);
        }
        $labels = [];
        $data = [];
        foreach ($records->get() as $record) {
            $percentage = (($record->sale_leads - $record->created_sale_leads) / (($record->total_leads - $record->bad_leads - $record->manual_created) > 0 ? ($record->total_leads - $record->bad_leads - $record->manual_created) : 1));
            $data[] = number_format((float) $percentage, 2, '.', '');
            $labels[] = $record->name;
        }

        return $request->tier_filter ? [json_encode($labels, JSON_OBJECT_AS_ARRAY), json_encode($data, JSON_OBJECT_AS_ARRAY)] : [$labels, $data];
    }

    public function renderComprehensiveDashboard(Request $request)
    {
        $carTeam = Team::where('name', quoteTypeCode::Car)->first();
        $carUsers = User::where('team_id', $carTeam->id)->orderBy('name', 'asc')->get();
        $tiers = Tier::whereNotIn('name', [TiersEnum::TierTR, TiersEnum::Tier6])->orderBy('name', 'asc')->get();
        $stats = $this->getComprehensiveDashboardStats($request);

        return view('dashboard.comprehensive_dashboard', compact('carUsers', 'tiers'))
                ->with('labels', json_encode($stats[0], JSON_OBJECT_AS_ARRAY))
                ->with('data', json_encode($stats[1], JSON_OBJECT_AS_ARRAY));
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
