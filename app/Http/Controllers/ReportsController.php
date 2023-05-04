<?php

namespace App\Http\Controllers;

use App\Services\AdvisorConversionReportService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function renderAdvisorConversionReport(Request $request, AdvisorConversionReportService $advisorConversionReportService)
    {
        return inertia('Reports/AdvisorConversion', [
            'reportData' => $advisorConversionReportService->getReportData(),
            'filterOptions' => $advisorConversionReportService->getFilterOptions(),
            'defaultFilters' => $advisorConversionReportService->getDefaultFilters(),
        ]);
    }

    public function fetchAdvisorAssignedLeadsData(Request $request, AdvisorConversionReportService $advisorConversionReportService)
    {
        $filters = [
            'advisorId' => $request->advisorId,
            'leadType' => $request->leadType,
            'advisorAssignedDates' => $request->advisor_assigned_dates,
            'createdAtFilter' => $request->createdAtFilter,
            'ecommerceFilter' => $request->is_ecommerce,
            'excludeCreatedLeadsFilter' => $request->excludeCreatedLeadsFilter,
            'batchNumberFilter' => $request->batches,
            'tiersFilter' => $request->tiers,
            'leadSourceFilter' => $request->leadSources,
            'teamsFilter' => $request->teams,
            'advisorsFilter' => $request->advisors,
            'quoteBatchId' => $request->quote_batch_id,
        ];

        return $advisorConversionReportService->getAdvisorsAssignedLeads($filters);
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
