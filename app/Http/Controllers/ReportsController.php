<?php

namespace App\Http\Controllers;

use App\Enums\AssignmentTypeEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RetentionReportEnum;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Factories\ManagementReportServiceFactory;
use App\Models\Department;
use App\Models\RenewalBatch;
use App\Models\Team;
use App\Models\User;
use App\Models\UserManager;
use App\Services\ConversionAsAtReportService;
use App\Services\DropdownSourceService;
use App\Services\Reports\AdvisorConversionReportService;
use App\Services\Reports\AdvisorDistributionReportService;
use App\Services\Reports\AdvisorPerformanceReportService;
use App\Services\Reports\LeadDistributionReportService;
use App\Services\Reports\RenewalBatchReportService;
use App\Services\Reports\ReportService;
use App\Services\Reports\RetentionReportService;
use App\Strategies\ManagementReport;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDF;

class ReportsController extends Controller
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function __construct()
    {
        $advisorConverionReportPermissions = implode('|', array_merge(PermissionsEnum::getAdvisorConversionReportPermissions(), [PermissionsEnum::VIEW_ALL_REPORTS]));
        $this->middleware(['permission:'.$advisorConverionReportPermissions], ['only' => ['renderAdvisorConversionReport']]);

        $advisorDistributionReportPermissions = implode('|', array_merge(PermissionsEnum::getAdvisorDistributionReportPermissions(), [PermissionsEnum::VIEW_ALL_REPORTS]));
        $this->middleware(['permission:'.$advisorDistributionReportPermissions], ['only' => ['renderAdvisorDistributionReport']]);

        $this->middleware('readonly_db');
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
            'isEmbeddedProducts' => $request->isEmbeddedProducts,
            'lob' => $request->lob,
            'subteams' => $request->sub_teams,
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
            'filtersByLob' => $advisorDistributionReportService->getFiltersByLob(),
            'filterOptions' => $advisorDistributionReportService->getFilterOptions(),
            'defaultFilters' => $advisorDistributionReportService->getDefaultFilters(),
            'assignmentTypes' => AssignmentTypeEnum::withLabels(),
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

    public function renderRevivalConversionReport(Request $request, ReportService $reportService)
    {

        $allowedLobs = [
            QuoteTypeId::Car => QuoteTypes::CAR->value,
            QuoteTypeId::Health => QuoteTypes::HEALTH->value,
        ];

        $reportData = $reportService->getRevivalReportsData($request);

        return inertia('Reports/RevivalConversion', [
            'reportsData' => $reportData,
            'allowedLobs' => $allowedLobs,
            'quoteTypeIdEnum' => QuoteTypeId::asArray(),
        ]);
    }

    /**
     * Fetches the team list based on the line of business (LOB) requested.
     *
     * @param  Request  $request  The HTTP request object.
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
        $usersReportToLoggedInUser = $this->getUsersByProductName($request->lob);
        if (! auth()->user()->hasAnyRole([
            RolesEnum::SeniorManagement,
            RolesEnum::Admin,
            RolesEnum::Engineering,
        ])
            &&
            ! auth()->user()->can(PermissionsEnum::VIEW_ALL_REPORTS)
        ) {
            $usersReportToLoggedInUser = UserManager::where('manager_id', auth()->user()->id)
                ->whereIn('user_id', $usersReportToLoggedInUser)->pluck('user_id')->toArray();
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
     * @param  Request  $request  The HTTP request object.
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
            // Managers can see only advisors assigned to them
            $teamUsers = $this->getUsersByTeamIds($request->teamIds)->pluck('id')->toArray();
            $advisorIdsByTeamQuery = UserManager::whereIn('user_id', $teamUsers);
            if (! auth()->user()->can(PermissionsEnum::VIEW_ALL_REPORTS)) {
                $advisorIdsByTeamQuery->where('manager_id', auth()->user()->id);
            }
            $advisorIdsByTeam = $advisorIdsByTeamQuery->pluck('user_id')->toArray();
        }

        return User::whereIn('id', $advisorIdsByTeam)
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->toArray();
    }

    public function fetchAdvisorListByDepartment(Request $request)
    {
        return User::select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->where('department_id', $request->department_id)
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
            // Managers can see only advisors assigned to them
            $advisorIdsByTeam = UserManager::where('manager_id', auth()->user()->id)
                ->whereIn('user_id', $teamUsers)->pluck('user_id')->toArray();
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
        }])->where('quote_type_id', QuoteTypeId::Car)->get();

        $renewalBatches = $renewalBatches->map(function ($renewalBatch) {
            $renewalBatch->slabs = $renewalBatch->slabs->map(function ($slab) {
                $slab->team_name = $slab->pivot->team->name;

                return $slab;
            });

            return $renewalBatch;
        });

        return inertia('Reports/RenewalBatch', [
            'reportData' => $renewalBatchReportService->getReportData($request),
            'superRetentionData' => $renewalBatchReportService->getSuperRetentionData($request),
            'filterOptions' => $renewalBatchReportService->getFilterOptions(),
            'renewalBatchesList' => $renewalBatches,
        ]);
    }

    public function renderPaymentSummary(Request $request, ReportService $reportService)
    {
        $user = Auth::user();
        $request->teamIds = $user->getUserTeamsIds($user->id);
        if ($request->quoteType) {
            $quoteType = explode(' ', Str::lower(trim($request->quoteType)))[0];
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($quoteType));
            $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', $quoteTypeId);
            $leadStatuses = $leadStatuses->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->text,
                ];
            })->toArray();
        }
        $fieldDisable = true;
        if ($user->isAdvisor()) {
            $advisor = [
                [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
            ];
            $fieldDisable = false;
        } else {
            $advisor = $this->fetchAdvisorsByTeam($request);
        }

        return inertia('Reports/AuthorisedPaymentSummary', [
            'reportData' => $reportService->getPaymentAuthorisedSummary($request),
            'defaultFilters' => $reportService->authorizedPaymentSummaryFilters(),
            'advisor' => $advisor,
            'fieldDisable' => $fieldDisable,
            'leadStatuses' => $leadStatuses ?? [],
        ]);
    }

    public function renderConversionAsAtReport(Request $request, ConversionAsAtReportService $conversionAsAtReportService)
    {

        $displayBy = $request->displayBy ?? null;
        $createdAtDate = $request->createdAtDate ?? null;
        $includeUnassignedLeads = $request->includeUnassignedLeads ?? null;
        $quoteTypes = QuoteTypeId::getOptions();
        $quoteTypeCodes = quoteTypeCode::asArray();
        $quoteTypeIdEnum = QuoteTypeId::asArray();

        return inertia('Reports/ConversionAsAt', [
            'reportData' => $conversionAsAtReportService->getReportData($request),
            'unassignedLeadsCount' => $conversionAsAtReportService->getUnassignedLeadsCount($request),
            'filterOptions' => $conversionAsAtReportService->getFilterOptions(),
            'quoteTypes' => $quoteTypes,
            'displayByColumn' => $displayBy,
            'createdAtDate' => $createdAtDate,
            'includeUnassignedLeads' => $includeUnassignedLeads,
            'quoteTypeCodes' => $quoteTypeCodes,
            'quoteTypeIdEnum' => $quoteTypeIdEnum,
        ]);
    }

    public function conversionAsAtReportPdf(Request $request, ConversionAsAtReportService $conversionAsAtReportService)
    {
        $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
        $timeOnlyFormat = config('constants.TIME_ONLY_FORMAT');
        $dateTimeFormat = config('constants.DATETIME_DISPLAY_FORMAT');

        $displayByColumn = $request->displayBy ?? null;
        $displayBy = $request->displayBy ? ucfirst(str_replace('_', ' ', $request->displayBy)) : 'N/A';
        $lob = QuoteTypes::getName($request->lob)->value.' Insurance';

        $reportData = $conversionAsAtReportService->getReportData($request);
        $totalGrossConversion = $conversionAsAtReportService->calculateTotalGrossConversion($reportData);
        $totalNetConversion = $conversionAsAtReportService->calculateTotalNetConversion($reportData);
        // this is explicitly pdf data, if I set name to 'data' then may be some dev(s) may get confused about it
        // that what this data may refers to, so to avoid confusion I am specifying it as pdfData.
        // Thanks
        $pdfDate = [
            'report_data' => $reportData->toArray(),
            'total_gross_conversion' => $totalGrossConversion,
            'total_net_conversion' => $totalNetConversion,
            'lob' => $lob,
            'display_by_column' => $displayByColumn,
            'display_by' => $displayBy,
            'start_date' => Carbon::parse($request->startEndDate[0])->format($dateFormat),
            'end_date' => Carbon::parse($request->startEndDate[1])->format($dateFormat),
            'as_at_date' => Carbon::parse($request->asAtDate)->format($dateFormat),
            'title' => 'Conversion As At Report',
            'auth' => auth()->user()->name,
            'date' => date($dateFormat),
            'time' => now()->format($timeOnlyFormat),
        ];

        $pdf = PDF::loadView('pdf.conversion_as_at_report', compact('pdfDate'))->setOptions(['defaultFont' => 'DejaVu Sans']);
        $name = 'InsuranceMarket.ae™ Conversion As At Report - '.Carbon::now()->format($dateTimeFormat).'.pdf';

        return response()->json(['data' => 'data:application/pdf;base64,'.base64_encode($pdf->stream()), 'name' => $name]);
    }

    public function renderStaleLeadsReport(Request $request, ReportService $reportService)
    {
        $data = $reportService->getStaleLeadsReport($request, true)->simplePaginate(15)->appends(request()->query());

        $team = auth()->user()->teams()->get();
        $productIds = DB::table('user_products')->where('user_id', auth()->user()->id)->get()->pluck('product_id');
        $quoteTypes = [
            QuoteTypes::HEALTH,
            QuoteTypes::HOME,
            QuoteTypes::PET,
            QuoteTypes::CORPLINE,
            QuoteTypes::CYCLE,
            QuoteTypes::YACHT,
        ];

        $products = Team::whereIn('id', $productIds)->where('type', TeamTypeEnum::PRODUCT)->where('is_active', 1)->get();

        return inertia('Reports/StaleLeadsReport', [
            'reportData' => $data,
            'teams' => $team,
            'products' => $products->pluck('name')->toArray(),
        ]);
    }

    public function renderSaleManagementReport(Request $request)
    {
        $shouldLoadData = isset($request->reportCategory);
        $reportCategory = ! isset($request->reportCategory) ? ManagementReportCategoriesEnum::SALE_SUMMARY : $request->reportCategory;
        $reportInstance = ManagementReportServiceFactory::createStrategy($reportCategory);
        $reportData = null;
        $endorsementData = null;
        if ($reportCategory == ManagementReportCategoriesEnum::SALE_SUMMARY && $shouldLoadData) {
            $rawReportData = $reportInstance->getReportData($request);
            $endorsementData = $reportInstance->getEndorsementsData($request);
            /**
             * process the endorsements data
             */
            $reportData = ManagementReport::processEndorsementsData($rawReportData, $endorsementData, $request);
            $reportInstance->formatData($reportData);
        }

        return inertia('ManagementReport/index', [
            'reportData' => $shouldLoadData ? ($reportData ?? $reportInstance->getReportData($request, $shouldLoadData)) : [],
            'filterOptions' => $reportInstance->getFilterOptions(),
            'defaultFilters' => $reportInstance->getDefaultFilters(),
            'reportName' => $reportCategory,
        ]);
    }

    /**
     * export method for management reports.
     *
     * @return void
     */
    public function exportManagementReport(Request $request)
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

    public function renderRetentionReport(Request $request, RetentionReportService $retentionReportService)
    {
        @[$retentionReportData, $footerData] = $retentionReportService->getReportData($request);

        return inertia('Reports/RetentionReport', [
            'filterOptions' => $retentionReportService->getFilterOptions(),
            'filtersByLob' => $retentionReportService->getFiltersByLob(),
            'reportData' => $retentionReportData,
            'footerData' => $footerData,
            'productName' => $retentionReportService->getUserPorductName(),
            'retentionReportEnum' => RetentionReportEnum::asArray(),
            'departments' => Department::where('is_active', true)->whereIn('id', Auth::user()->departments->pluck('id')->toArray())->get()
                ->map(function ($department) {
                    return [
                        'value' => $department->id,
                        'label' => $department->name,
                    ];
                })->toArray(),
        ]);
    }

    public function fetchRetentionLeadsData(Request $request, RetentionReportService $retentionReportService)
    {
        return $retentionReportService->getRetentionLeadsData($request);
    }

    public function fetchBatchByDates(Request $request, RetentionReportService $retentionReportService)
    {
        return $retentionReportService->getBatchByDates($request);
    }
}
