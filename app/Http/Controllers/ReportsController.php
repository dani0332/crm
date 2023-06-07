<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatusEnum;
use App\Models\QuoteType;
use App\Models\User;
use App\Repositories\QuoteTypeRepository;
use App\Services\AdvisorConversionReportService;
use App\Services\AdvisorDistributionReportService;
use App\Services\AdvisorPerformanceReportService;
use App\Services\LeadDistributionReportService;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    use TeamHierarchyTrait;
    use GetUserTreeTrait;

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


    public function utmLeadsSaleReport(Request $request)
    {
        $records = [];
        if ($request->has('quote_type_id') && $request->has('group_by_one')) {

            $quoteTypeCode = QuoteType::where('id', '=', $request->quote_type_id)->value('code');
            $model = 'App\Models\\' . $quoteTypeCode . 'Quote';
            $quoteRequestTable = strtolower($quoteTypeCode) . '_quote_request';

            $group_by_one = $request->group_by_one;
            $group_by_two = $request->group_by_two;

            $groupBy[] = $group_by_one;
            if (!empty($group_by_two)) {
                $groupBy[] = $group_by_two;
            }

            $records = $model::query()
                ->select(
                    'utm_source',
                    'utm_medium',
                    'utm_campaign',
                    DB::raw('count(' . $quoteRequestTable . '.id) as leads_count'),
                    DB::raw('COUNT(CASE  WHEN payment_status_id = 4 THEN 1 ELSE NULL END) as authorized'),
                    DB::raw('COUNT(CASE  WHEN payment_status_id = 6 THEN 1 ELSE NULL END) as captured'),

                    DB::raw('sum(CASE WHEN payment_status_id = 4 THEN premium  ELSE 0 END) as authorized_sum'),
                    DB::raw('sum(CASE WHEN payment_status_id = 6 THEN premium  ELSE 0 END) as captured_sum'),


                )
                ->join($quoteRequestTable . '_detail', $quoteRequestTable . '.id', $quoteRequestTable . '_detail.' . $quoteRequestTable . '_id')
                ->where(function ($query) {
                    $query->where('payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
                        ->orWhere('payment_status_id', '=', PaymentStatusEnum::CAPTURED);
                })
                ->where('payment_status_id', '<>', '')
                ->where('utm_source', '<>', '')
                ->where('utm_medium', '<>', '')
                ->where('utm_campaign', '<>', '');
            if (!empty($request->date_from) && !empty($request->date_to)) {

                $date_from = date('Y-m-d 00:00:00', strtotime(request()->date_from));
                $date_to = date('Y-m-d 23:59:59', strtotime(request()->date_to));

                $records->whereBetween($quoteRequestTable . '.created_at', [$date_from, $date_to]);
            }
            $records->groupBy($groupBy)
                ->simplePaginate(10)->withQueryString();
        }


        return inertia('Reports/UtmLeadsSale', [
            'quoteTypes' => QuoteTypeRepository::getList(),
            'reportData' => $records,
        ]);
    }
}
