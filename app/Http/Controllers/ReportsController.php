<?php

namespace App\Http\Controllers;

use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\PermissionsEnum;
use App\Factories\ManagementReportServiceFactory;
use App\Models\RenewalBatch;
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
        $this->middleware(
            'permission:'.PermissionsEnum::DATA_EXTRACTION,
            [
                'only' => 'exportSaleManagementReport',
            ]
        );
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

    /**
     * generate renewal reports function
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

    /**
     * export method for management reports
     *
     * @return void
     */
    public function exportSaleManagementReport(Request $request)
    {
        $reportCategory = ! isset($request->reportCategory) ? ManagementReportCategoriesEnum::SALE_SUMMARY : $request->reportCategory;
        $reportInstance = ManagementReportServiceFactory::createStrategy($reportCategory);

        return $reportInstance->getReportData($request);
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
