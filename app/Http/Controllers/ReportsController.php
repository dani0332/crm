<?php

namespace App\Http\Controllers;

use App\Services\AdvisorConversionReportService;

class ReportsController extends Controller
{
    public function renderAdvisorConversionReport(AdvisorConversionReportService $advisorConversionReportService)
    {
        return inertia('Reports/AdvisorConversion', [
            'data' => $advisorConversionReportService->getReportData(),
        ]);
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
