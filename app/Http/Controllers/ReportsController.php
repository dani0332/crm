<?php

namespace App\Http\Controllers;

use App\Services\AdvisorConversionReportService;
use App\Services\AdvisorDistributionReportService;
use App\Services\AdvisorPerformanceReportService;
use App\Services\LeadDistributionReportService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function renderAdvisorConversionReport(Request $request, AdvisorConversionReportService $advisorConversionReportService)
    {
        return inertia('Reports/AdvisorConversion', [
            'reportData' => $advisorConversionReportService->getReportData($request),
            'filterOptions' => $advisorConversionReportService->getFilterOptions(),
            'defaultFilters' => $advisorConversionReportService->getDefaultFilters(),
        ]);
    }

    public function fetchAdvisorAssignedLeadsData(Request $request, AdvisorConversionReportService $advisorConversionReportService)
    {
        $filters = [
            'advisorId' => $request->advisorId,
            'leadType' => $request->leadType,
            'advisorAssignedDates' => $request->advisorAssignedDates,
            'createdAtFilter' => $request->createdAtFilter,
            'ecommerceFilter' => $request->is_ecommerce,
            'excludeCreatedLeadsFilter' => $request->excludeCreatedLeadsFilter,
            'batchNumberFilter' => $request->batches,
            'tiersFilter' => $request->tiers,
            'leadSourceFilter' => $request->leadSources,
            'teamsFilter' => $request->teams,
            'advisorsFilter' => $request->advisors,
            'quoteBatchId' => $request->quote_batch_id,
            'page' => $request->page,
        ];

        return $advisorConversionReportService->getAdvisorsAssignedLeads($filters);
    }

    public function renderLeadDistributionReport(Request $request, LeadDistributionReportService $leadDistributionReportService)
    {
        return inertia('Reports/LeadDistribution', [
            'reportData' => $leadDistributionReportService->getReportData($request),
            'filterOptions' => $leadDistributionReportService->getFilterOptions(),
            'defaultFilters' => $leadDistributionReportService->getDefaultFilters(),
        ]);
    }

    public function renderAdvisorDistributionReport(Request $request, AdvisorDistributionReportService $advisorDistributionReportService)
    {
        return inertia('Reports/AdvisorDistribution', [
            'reportData' => $advisorDistributionReportService->getReportData($request),
            'filterOptions' => $advisorDistributionReportService->getFilterOptions(),
            'defaultFilters' => $advisorDistributionReportService->getDefaultFilters(),
        ]);
    }

    public function renderAdvisorPerformanceReport(Request $request, AdvisorPerformanceReportService $advisorPerformanceReportService)
    {
        return inertia('Reports/AdvisorPerformance', [
            'reportData' => $advisorPerformanceReportService->getReportData($request),
            'filterOptions' => $advisorPerformanceReportService->getFilterOptions(),
            'defaultFilters' => $advisorPerformanceReportService->getDefaultFilters(),
        ]);
    }

    public function renderLeadListReport()
    {
        return view('reports.lead-list-report');
    }
}
