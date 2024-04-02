<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\RenewalBatch;
use App\Models\User;
use App\Services\AdvisorConversionReportService;
use App\Services\AdvisorDistributionReportService;
use App\Services\AdvisorPerformanceReportService;
use App\Services\LeadDistributionReportService;
use App\Services\RenewalBatchReportService;
use App\Services\ReportService;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Http\Request;
use App\Enums\TeamNameEnum;
use App\Enums\quoteTypeCode;
use Illuminate\Support\Facades\Auth;
use App\Enums\PermissionsEnum;

class ReportsController extends Controller
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function __construct()
    {
        $advisorConverionReportPermissions = implode('|', PermissionsEnum::getAdvisorConverionReportPermissions());
        $this->middleware(['permission:' . $advisorConverionReportPermissions],['only' => ['renderAdvisorConversionReport']]);
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

    /**
     * Fetches the team list based on the line of business (LOB) requested.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object.
     * @return array The array of team names and IDs.
     */
    public function fetchTeamListByLob(Request $request)
    {
        $names = [];
        if($request->lob === quoteTypeCode::Car) {
            $names = [
                TeamNameEnum::ORGANIC,
                TeamNameEnum::BDM,
                TeamNameEnum::SBDM,
                TeamNameEnum::RENEWALS,
                TeamNameEnum::MOTOR_CORPORATE_NB_COMMERCIAL,
                TeamNameEnum::MOTOR_COOPERATE_RENEWALS,
            ];
        } else if($request->lob === quoteTypeCode::Health) {
            $names = [
                TeamNameEnum::RM_NB,
                TeamNameEnum::RM_SPEED,
                TeamNameEnum::EBP,
            ];
        } else if($request->lob === quoteTypeCode::Business) {
            $names = [
                TeamNameEnum::NEW_BUSINESS,
                TeamNameEnum::BUSINESS_RENEWALS,
            ];
        } else if($request->lob === quoteTypeCode::GroupMedical) {
            $names = [
                TeamNameEnum::AMT,
            ];
        }

        $lobId = $this->getProductByName($request->lob)->id;
        $allTeams = $this->getTeamsByProductId($lobId)->pluck('id')->toArray();
        $userTeams = $this->getUserTeams(auth()->user()->id)->pluck('id')->toArray();
        $commonteamIds = array_intersect($allTeams, $userTeams);

        $teams = Team::whereIn('id', $commonteamIds)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1);

        if (count($names) > 0) {
            $teams = $teams->whereIn('name', $names);
        }

        return $teams->get()->toArray();
    }

    /**
     * Fetches the list of advisors by line of business (LOB).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fetchAdvisorsListByLob(Request $request)
    {
        $loginUserId = auth()->user()->id;
        $userProducts = $this->getUsersByProductName($request->lob)->pluck('id')->toArray();

        $usersReportToLoggedInUser = $this->walkTree($loginUserId);
        $advisorIdsByTeam = array_intersect($userProducts, $usersReportToLoggedInUser);

        return User::whereIn('id', $advisorIdsByTeam)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->toArray();
    }

    /**
     * Fetches the list of sub-teams based on the given team IDs and the current user's teams and sub-teams.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object.
     * @return array The list of sub-teams as an array of associative arrays containing 'name' and 'id' keys.
     */
    public function fetchSubTeamListByTeam(Request $request)
    {
        $allowedSubTeams = [TeamNameEnum::VALUE, TeamNameEnum::VOLUME, TeamNameEnum::MICRO_SME];
        $subTeams = $this->getSubTeamsByTeamIds($request->teamIds)->whereIn('name', $allowedSubTeams)->pluck('id')->toArray();
        $userTeams = $this->getCurrentUserTeamsAndSubTeams(Auth::user()->id)->pluck('id')->toArray();
        $ids = array_intersect($subTeams, $userTeams);
        return Team::whereIn('id', $ids)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->toArray();
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
}
