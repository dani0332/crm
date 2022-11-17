<?php

namespace App\Http\Controllers;

use App\Charts\MainDashboardChart;
use App\Services\DashboardService;

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
    public function index(MainDashboardChart $mainDashboardChart)
    {
        return view('dashboard.main_dashboard', ['chart' => $mainDashboardChart->build()]);
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
