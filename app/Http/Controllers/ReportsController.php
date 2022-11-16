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

    }

    public function renderAdvisorConversionReport()
    {
        return view('reports.advisor-conversion-report');
    }

    public function renderLeadDistributionReport()
    {
        return view('reports.lead-distribution-report');
    }

    public function renderAdvisorDistributionReport()
    {
        return view('reports.advisor-distribution-report');
    }

    public function renderAdvisorPerformanceReport()
    {
        return view('reports.advisor-performance-report');
    }

    public function renderLeadListReport()
    {
        return view('reports.lead-list-report');
    }
}
