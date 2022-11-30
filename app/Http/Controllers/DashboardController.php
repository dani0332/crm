<?php

namespace App\Http\Controllers;

use App\Charts\ComprehensiveDashboard;
use App\Charts\MainDashboardChart;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\TiersEnum;
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

    public function renderMainDashboard(MainDashboardChart $mainDashboardChart)
    {
        return view('dashboard.main_dashboard', ['chart' => $mainDashboardChart->build()]);
    }

    public function renderTplDashboard(Request $request)
    {
        $stats = $this->getTPLDashboardStats($request);

        return view('dashboard.tpl_dashboard')
                ->with('labels', json_encode($stats[0], JSON_OBJECT_AS_ARRAY))
                ->with('data', json_encode($stats[1], JSON_OBJECT_AS_ARRAY));
    }

    public function getTPLDashboardStats(Request $request)
    {
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
            $request->tier_filter == 'tr' ? $records->where('tiers.name', TiersEnum::TierTR) : $records->where('tiers.name', TiersEnum::Tier6);
        }
        if (isset($request->source)) {
            if ($request->source == 'no') {
                $records->where('car_quote_request.source', LeadSourceEnum::IMCRM);
            }
            if ($request->source == 'yes') {
                $records->where('car_quote_request.source', '!=', LeadSourceEnum::IMCRM);
            }
        }

        $labels = [];
        $data = [];
        foreach ($records->get() as $record) {
            $percentage = (($record->sale_leads - $record->created_sale_leads) / (($record->total_leads - $record->bad_leads - $record->manual_created) > 0 ? ($record->total_leads - $record->bad_leads - $record->manual_created) : 1));
            array_push($data, $percentage);
            array_push($labels, $record->name);
        }

        return $request->tier_filter ? [json_encode($labels, JSON_OBJECT_AS_ARRAY), json_encode($data, JSON_OBJECT_AS_ARRAY)] : [$labels, $data];
    }

    public function getComprehensiveDashboardStats(Request $request, $tiers)
    {
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
        ->whereIn('tiers.id', $compTiers)
        ->groupBy('quote_batches.name', 'quote_batches.id')->skip(0)->take(10)->orderBy('quote_batches.id', 'desc');
        if (isset($request->tier_filter)) {
            $request->tier_filter == 'tr' ? $records->where('tiers.name', TiersEnum::TierTR) : $records->where('tiers.name', TiersEnum::Tier6);
        }
        if (isset($request->source)) {
            if ($request->source == 'no') {
                $records->where('car_quote_request.source', LeadSourceEnum::IMCRM);
            }
            if ($request->source == 'yes') {
                $records->where('car_quote_request.source', '!=', LeadSourceEnum::IMCRM);
            }
        }

        $labels = [];
        $data = [];
        foreach ($records->get() as $record) {
            $percentage = (($record->sale_leads - $record->created_sale_leads) / (($record->total_leads - $record->bad_leads - $record->manual_created) > 0 ? ($record->total_leads - $record->bad_leads - $record->manual_created) : 1));
            array_push($data, $percentage);
            array_push($labels, $record->name);
        }

        return $request->tier_filter ? [json_encode($labels, JSON_OBJECT_AS_ARRAY), json_encode($data, JSON_OBJECT_AS_ARRAY)] : [$labels, $data];
    }

    public function renderComprehensiveDashboard(Request $request)
    {
        $carTeam = Team::where('name', quoteTypeCode::Car)->first();
        $carUsers = User::where('team_id', $carTeam->id)->get();
        $tiers = Tier::whereNotIn('name',  [TiersEnum::TierTR, TiersEnum::Tier6])->get();
        $stats = $this->getComprehensiveDashboardStats($request, $tiers);

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

    public function getWeeklyStats($type)
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

    public function getWeeklyHeading()
    {
        return [
            '1WeekHeadingDate' => $this->dashboardService->getWeekHeadingDate(0),
            '2WeekHeadingDate' => $this->dashboardService->getWeekHeadingDate(1),
            '3WeekHeadingDate' => $this->dashboardService->getWeekHeadingDate(2),
            '4WeekHeadingDate' => $this->dashboardService->getWeekHeadingDate(3),
        ];
    }
}
