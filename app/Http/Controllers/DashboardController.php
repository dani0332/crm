<?php

namespace App\Http\Controllers;

use App\Models\CarQuote;
use App\Models\Customer;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $thisWeekStats = $this->dashboardService->getDashboardStatsByDate(
            $this->dashboardService->getPastDateByWeek(0, true),
            $this->dashboardService->getPastDateByWeek(0, false)
        );
        $secondWeekStats = $this->dashboardService->getDashboardStatsByDate(
            $this->dashboardService->getPastDateByWeek(1, true),
            $this->dashboardService->getPastDateByWeek(1, false)
        );
        $thirdWeekStats = $this->dashboardService->getDashboardStatsByDate(
            $this->dashboardService->getPastDateByWeek(2, true),
            $this->dashboardService->getPastDateByWeek(2, false)
        );
        $fourthWeekStats = $this->dashboardService->getDashboardStatsByDate(
            $this->dashboardService->getPastDateByWeek(3, true),
            $this->dashboardService->getPastDateByWeek(3, false)
        );

        $firstWeekHeadingDate = $this->dashboardService->getWeekHeadingDate(0);
        $secondWeekHeadingDate = $this->dashboardService->getWeekHeadingDate(1);
        $thirdWeekHeadingDate = $this->dashboardService->getWeekHeadingDate(2);
        $fourthWeekHeadingDate = $this->dashboardService->getWeekHeadingDate(3);

        return view('dashboard', compact('thisWeekStats', 'secondWeekStats', 'thirdWeekStats', 'fourthWeekStats', 'firstWeekHeadingDate', 'secondWeekHeadingDate', 'thirdWeekHeadingDate', 'fourthWeekHeadingDate'));
    }
}
