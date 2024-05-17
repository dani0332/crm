<?php

namespace App\Http\Controllers;

use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
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
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function __construct()
    {
        $advisorConverionReportPermissions = implode('|', PermissionsEnum::getAdvisorConverionReportPermissions());
        $this->middleware(['permission:'.$advisorConverionReportPermissions], ['only' => ['renderAdvisorConversionReport']]);
    }

    public function renderAdvisorConversionReport(Request $request, AdvisorConversionReportService $advisorConversionReportService)
    {
        return inertia('Reports/AdvisorConversion', [
            'reportData' => $advisorConversionReportService->getReportData($request),
            'filtersByLob' => $advisorConversionReportService->getFiltersByLob(),
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
            'isCommercial' => $request->isCommercial,
            'lob' => $request->lob,
            'subeams' => $request->sub_teams,
            'vehicle_type' => $request->vehicle_type,
            'insurance_type' => $request->insurance_type,
            'insurance_for' => $request->insurance_for,
            'travel_coverage' => $request->travel_coverage,
            'segment_filter' => $request->segment_filter,
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

    /**
     * Fetches the team list based on the line of business (LOB) requested.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request object.
     * @return array The array of team names and IDs.
     */
    public function fetchTeamListByLob(Request $request)
    {
        $lobId = $this->getProductByName($request->lob)->id;
        $allTeams = $this->getTeamsByProductId($lobId)->pluck('id')->toArray();

        if (auth()->user()->hasAnyRole([
            RolesEnum::SeniorManagement,
            RolesEnum::Admin,
            RolesEnum::Engineering,
        ])) {
            $commonteamIds = $allTeams;
        } else {
            $userTeams = $this->getUserTeams(auth()->user()->id)->pluck('id')->toArray();
            $commonteamIds = array_intersect($allTeams, $userTeams);
        }

        $teams = Team::whereIn('id', $commonteamIds)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1);

        return $teams->get()->toArray();
    }

    /**
     * Fetches the list of advisors by line of business (LOB).
     *
     * @return array
     */
    public function fetchAdvisorsListByLob(Request $request)
    {
        $loginUserId = auth()->user()->id;
        if (
            auth()->user()->hasAnyRole([
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ])
        ) {
            $usersReportToLoggedInUser = $this->getUsersByProductName($request->lob)->pluck('id')->toArray();
        } else {
            $usersReportToLoggedInUser = $this->walkTree($loginUserId, $request->lob);

            if (auth()->user()->isManagerOrDeputy()) {
                $usersReportToLoggedInUser = array_filter($usersReportToLoggedInUser, function ($userId) use ($loginUserId) {
                    return $userId !== $loginUserId;
                });
            }
        }

        return User::whereIn('id', $usersReportToLoggedInUser)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->toArray();
    }

    /**
     * Fetches the list of sub-teams based on the given team IDs and the current user's teams and sub-teams.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request object.
     * @return array The list of sub-teams as an array of associative arrays containing 'name' and 'id' keys.
     */
    public function fetchSubTeamListByTeam(Request $request)
    {
        $subTeams = $this->getSubTeamsByTeamIds($request->teamIds)->pluck('id')->toArray();
        if (
            auth()->user()->hasAnyRole([
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ])
        ) {
            $ids = $subTeams;
        } else {
            $userTeams = $this->getCurrentUserTeamsAndSubTeams(Auth::user()->id)->pluck('id')->toArray();
            $ids = array_intersect($subTeams, $userTeams);
        }

        return Team::whereIn('id', $ids)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->toArray();
    }

    public function fetchAdvisorListByTeam(Request $request)
    {
        if (
            auth()->user()->hasAnyRole([
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ])
        ) {
            $advisorIdsByTeam = $this->getUsersByTeamIds($request->teamIds)->pluck('id')->toArray();
        } else {
            $usersReportToLoggedInUser = $this->walkTree(auth()->user()->id, $request->lob);
            $teamUsers = $this->getUsersByTeamIds($request->teamIds)->pluck('id')->toArray();
            $advisorIdsByTeam = array_intersect($teamUsers, $usersReportToLoggedInUser);

            if (auth()->user()->isManagerOrDeputy()) {
                $advisorIdsByTeam = array_filter($advisorIdsByTeam, function ($userId) {
                    return $userId !== auth()->user()->id;
                });
            }
        }

        return User::whereIn('id', $advisorIdsByTeam)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->toArray();
    }

    public function fetchAdvisorListBySubTeam(Request $request)
    {
        $teamUsers = $this->getUsersBySubTeamIds($request->teamIds)->pluck('id')->toArray();
        if (
            auth()->user()->hasAnyRole([
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ])
        ) {
            $advisorIdsByTeam = $teamUsers;
        } else {
            $usersReportToLoggedInUser = $this->walkTree(auth()->user()->id, $request->lob);
            $advisorIdsByTeam = array_intersect($teamUsers, $usersReportToLoggedInUser);

            if (auth()->user()->isManagerOrDeputy()) {
                $advisorIdsByTeam = array_filter($advisorIdsByTeam, function ($userId) {
                    return $userId !== auth()->user()->id;
                });
            }
        }

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

    public function totalPremiumLeadsSaleReport(Request $request, ReportService $reportService)
    {
        $resp = $reportService->totalPremiumReport($request);

        return inertia('Reports/TotalPremiumLeadsSale', [
            'reportData' => $resp ?? null,
            'filterOptions' => $reportService->getDefaultFiltersForTotalPremium(),
        ]);
    }

}
