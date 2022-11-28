<?php

namespace App\Http\Controllers;

use App\Charts\ComprehensiveDashboard;
use App\Charts\MainDashboardChart;
use App\Charts\TPLDashboard;
use App\Models\CarQuote;
use App\Models\CarTypeInsurance;
use App\Services\DashboardService;
use DB;

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

    public function renderTplDashboard(TPLDashboard $tPLDashboard)
    {
        $type = CarTypeInsurance::where('is_active', 1)->get();

        $records = CarQuote::query()
        ->select(
            'quote_batches.name',
            'quote_batches.start_date',
            'quote_batches.end_date',
            DB::raw('count(car_quote_request.id) as total_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM" THEN 1 ELSE 0 END) as manual_created'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in (9,35) THEN 1 ELSE 0 END) as bad_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 33 THEN 1 ELSE 0 END) as sale_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM" and car_quote_request.quote_status_id = 15 THEN 1 ELSE 0 END) as created_sale_leads'),
        )
        ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
        ->groupBy('quote_batches.name', 'quote_batches.id')
        ->orderBy('quote_batches.id', 'desc')->take(10)->get();

        $labels = [];
        $data = [];
        foreach ($records as $record) {
            $percentage = (($record->sale_leads - $record->created_sale_leads) / (($record->total_leads - $record->bad_leads - $record->manual_created) > 0 ? ($record->total_leads - $record->bad_leads - $record->manual_created) : 1));
            // $chart->addData($record->name.' ( '.$record->start_date.' to '.$record->end_date.' ) ', [$percentage.' %']);
            array_push($data, $percentage.' %');
            array_push($labels, $record->name);
        }

        return view('dashboard.tpl_dashboard', compact('labels', 'data'));
    }

    public function renderComprehensiveDashboard(ComprehensiveDashboard $comprehensiveDashboard)
    {
        return view('dashboard.comprehensive_dashboard', ['chart' => $comprehensiveDashboard->build()]);
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
