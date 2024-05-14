<?php

namespace App\Http\Controllers;

use App\Enums\ManagementReportCategoriesEnum;
use App\Factories\ManagementReportServiceFactory;
use App\Models\RenewalBatch;
use App\Models\Team;
use App\Models\User;
use App\Repositories\CarRevivalQuoteRepository;
use App\Services\Reports\AdvisorConversionReportService;
use App\Services\Reports\AdvisorDistributionReportService;
use App\Services\Reports\AdvisorPerformanceReportService;
use App\Services\Reports\LeadDistributionReportService;
use App\Services\Reports\RenewalBatchReportService;
use App\Services\Reports\ReportService;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function __construct()
    {
        $this->verifyPermissions([
            'renderAdvisorConversionReport' => 'ADVISOR_CONVERSION_REPORT_VIEW',
            'renderAdvisorPerformanceReport' => 'ADVISOR_PERFORMANCE_REPORT_VIEW',
            'renderAdvisorDistributionReport' => 'ADVISOR_DISTRIBUTION_REPORT_VIEW, BIKE_DISTRIBUTION_REPORT, HEALTH_DISTRIBUTION_REPORT, TRAVEL_DISTRIBUTION_REPORT, LIFE_DISTRIBUTION_REPORT, HOME_DISTRIBUTION_REPORT, PET_DISTRIBUTION_REPORT, CYCLE_DISTRIBUTION_REPORT, YACHT_DISTRIBUTION_REPORT, BUSINESS_DISTRIBUTION_REPORT, GROUPMEDICAL_DISTRIBUTION_REPORT',
            'renderLeadDistributionReport' => 'LEAD_DISTRIBUTION_REPORT_VIEW',
            'utmLeadsSaleReport' => 'UtmLeadsSalesReport',
            'renderRenewalReport' => 'RENEWAL_BATCH_REPORT',
            'renderStaleLeadsReport' => 'STALE_LEADS_REPORT',
        ]);
    }

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

    public function renderLeadListReport(Request $request, ReportService $reportService)
    {
        return inertia('Reports/LeadListReport', [
            'reportData' => $reportService->getLeadsListReport($request),
            'defaultFilters' => $reportService->getDefaultFiltersForLeadsList(),
        ]);
    }

    public function renderRevivalConversionReport(Request $request)
    {
        $reportData = CarRevivalQuoteRepository::getReportsData($request);

        return inertia('Reports/RevivalConversion', [
            'reportsData' => $reportData,
        ]);
    }

    public function fetchAdvisorListByTeam(Request $request)
    {
        $teamUsers = $this->getUsersByTeamIds($request->teamIds)->pluck('id')->toArray();

        $usersReportToLoggedInUser = $this->walkTree(auth()->user()->id);

        $advisorIdsByTeam = array_intersect($teamUsers, $usersReportToLoggedInUser);

        return User::whereIn('id', $advisorIdsByTeam)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->toArray();
    }

    public function fetchSubTeamsAdvisorListByTeam(Request $request)
    {
        $teamUsers = $this->getUsersByTeamIds($request->teamIds)->pluck('id')->toArray();

        $usersReportToLoggedInUser = $this->walkTree(auth()->user()->id);

        $advisorIdsByTeam = array_unique(array_merge($teamUsers, $usersReportToLoggedInUser));

        // subteams

        $subTeams = $this->getSubTeamsByTeamIds($request->teamIds)->toArray();

        $subTeams = array_reduce($subTeams, function ($carry, $item) {
            $carry[$item['id']] = $item['name'];

            return $carry;
        }, []);

        $advisors = User::whereIn('id', $advisorIdsByTeam)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->toArray();

        return [
            'advisors' => $advisors,
            'subTeams' => $subTeams,
        ];
    }

    public function utmLeadsSaleReport(Request $request, ReportService $reportService)
    {
        $resp = $reportService->utmReport($request);

        return inertia('Reports/UtmLeadsSale', [
            'quoteTypes' => $resp['lobs'],
            'reportData' => $resp['records'],
        ]);
    }

    public function renderPipelineReport(Request $request, ReportService $reportService)
    {
        $data = $reportService->getStaleLeadsReport($request)->simplePaginate(15)->appends(request()->query());

        return inertia('Reports/PipelineReport', [
            'reportData' => $data,
        ]);
    }

    public function fetchAdvisorsByTeam(Request $request)
    {
        $advisors = $this->getUsersByTeamIds($request->teamIds)->pluck('id')->toArray();

        return response()->json([
            'advisors' => User::whereIn('id', $advisors)
                ->select('name', 'id')
                ->orderBy('name')
                ->where('is_active', 1)
                ->get()
                ->toArray(),
        ]);
    }

    public function fetchTeamsbyType(Request $request)
    {
        $parentId = Team::where('name', $request->lob)->first()->id;
        $teams = Team::where('parent_team_id', $parentId)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();

        return response()->json([
            'teams' => $teams,
        ]);
    }

    /**
     * generate renewal reports function.
     *
     * @return void
     */
    public function renderRenewalReport(Request $request, RenewalBatchReportService $renewalBatchReportService)
    {
        $renewalBatches = RenewalBatch::with(['slabs' => function ($qry) {
            $qry->orderBy('id', 'desc');
        }, 'teams' => function ($qry) {
            $qry->whereIn('name', RenewalBatch::RENEWAL_BATCH_TEAMS_LIST);
        }])->get();

        $renewalBatches = $renewalBatches->map(function ($renewalBatch) {
            $renewalBatch->slabs = $renewalBatch->slabs->map(function ($slab) {
                $slab->team_name = $slab->pivot->team->name;

                return $slab;
            });

            return $renewalBatch;
        });

        return inertia('Reports/RenewalBatch', [
            'reportData' => $renewalBatchReportService->getReportData($request),
            'superRetentionData' => $renewalBatchReportService->getSuperRetentinoData($request),
            'filterOptions' => $renewalBatchReportService->getFilterOptions(),
            'defaultFilters' => $renewalBatchReportService->getDefaultFilters(),
            'renewalBatchesList' => $renewalBatches,
        ]);
    }

    public function renderStaleLeadsReport(Request $request, ReportService $reportService)
    {
        $data = $reportService->getStaleLeadsReport($request, true)->simplePaginate(15)->appends(request()->query());

        return inertia('Reports/StaleLeadsReport', [
            'reportData' => $data, ]);
    }

    public function renderSaleManagementReport(Request $request)
    {
        $reportCategory = ! isset($request->reportCategory) ? ManagementReportCategoriesEnum::SALE_SUMMARY : $request->reportCategory;
        $reportInstance = ManagementReportServiceFactory::createStrategy($reportCategory);

        return inertia('ManagementReport/index', [
            'reportData' => $reportInstance->getReportData($request),
            'filterOptions' => $reportInstance->getFilterOptions(),
            'defaultFilters' => $reportInstance->getDefaultFilters(),
            'reportName' => $reportCategory,
        ]);
    }

    public function totalPremiumLeadsSaleReport(Request $request, ReportService $reportService)
    {
        $resp = $reportService->totalPremiumReport($request);

        return inertia('Reports/TotalPremiumLeadsSale', [
            'reportData' => $resp ?? null,
            'filterOptions' => $reportService->getDefaultFiltersForTotalPremium(),
        ]);
    }
}
