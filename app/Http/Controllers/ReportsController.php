<?php

namespace App\Http\Controllers;

use App\Models\LeadSource;
use App\Models\Team;
use App\Models\Tier;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $batches = $this->reportService->generateBatchesFilterText();
        $teams = Team::whereNull('parent_team_id')->get();
        $users = User::where('is_active', 1)->get();
        $tiers = Tier::where('is_active', 1)->get();
        $leadSources = LeadSource::all();
        if ($request->ajax()) {
            if (isset($request->teamType)) {
                $teamName = strtolower($request->teamType);
            }
            if (Auth::user()->isRenewalAdvisor()) {
                $teamName = strtolower($request->leadType);
            }
            $allowedTypes = ['car', 'home', 'business', 'health', 'life', 'travel', 'pet'];
            $gridData = in_array($teamName, $allowedTypes) ? $this->reportService->getAdvisorConversionReportData($request, $teamName) : [];

            return DataTables::of($gridData)
                ->addIndexColumn()
                ->make(true);
        }

        return view('reports.advisor-conversion-report', compact('batches', 'teams', 'users', 'tiers', 'leadSources'));
    }
}
