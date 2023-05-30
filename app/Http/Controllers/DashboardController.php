<?php

namespace App\Http\Controllers;

use App\Enums\IMCRMSearchTypesEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\TiersEnum;
use App\Models\CarQuote;
use App\Models\QuoteBatches;
use App\Models\Team;
use App\Models\Tier;
use App\Services\DashboardService;
use App\Services\TierService;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use TeamHierarchyTrait;

    protected $dashboardService;
    protected $tierService;

    public function __construct(DashboardService $dashboardService, TierService $tierService)
    {
        $this->dashboardService = $dashboardService;
        $this->tierService = $tierService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard');
    }

    public function renderMainDashboard(Request $request)
    {
        $loggedInUserId = auth()->user()->id;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $startDate = now()->startOfDay()->format($dateFormat);
        $endDate = now()->endOfDay()->format($dateFormat);

        $todaysLeads = CarQuote::whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->get();
        $car = $this->getProductByName(quoteTypeCode::Car);
        $teams = $this->getCurrentUserTeamsAndSubTeams($loggedInUserId);
        $teamIds = DB::table('user_team')->where('user_id', $loggedInUserId)->get()->pluck('team_id');
        $carAdvisors = $this->getUsersByTeamId(count($teamIds->toArray()) > 0 ? $teamIds->toArray() : []);

        $filters = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'teams' => $this->getCurrentUserTeamsAndSubTeams($loggedInUserId),
            'teamIds' => $teamIds,
            'applyUnAssignedLeadsCountByTierDateFilter' => false,
            'applyTotalUnAssignedLeadsDateFilter' => false,
        ];
        $teamWiseLeadsAssignedAverage = $this->dashboardService->getTeamWiseLeadStats($filters);
        $totalLeadsReceived = count($todaysLeads);
        $totalLeadsReceivedEcommerce = count($todaysLeads->where('is_ecommerce', 1));
        $totalUnAssignedLeads = $this->dashboardService->getTotalUnAssignedLeads($filters);
        $totalUnAssignedLeadsReceived = count($totalUnAssignedLeads);
        $totalUnAssignedLeadsReceivedEcommerce = count($totalUnAssignedLeads->where('is_ecommerce', 1));
        $totalUnAssignedRevivalLeads = count($totalUnAssignedLeads->where('source', LeadSourceEnum::REVIVAL));

        $leadsCountByTier = $this->dashboardService->getLeadsCountByTier($filters);
        $unAssignedLeadsByTier = $this->dashboardService->getUnAssignedLeadsCountByTier($filters);
        $revivalLeadsCount = $this->dashboardService->getLeadsCountRevival($filters);
        $assignedLeadsBySource = $this->dashboardService->getAssignedLeadsCountBySource($filters);
        $advisorLeadsAssignedData = $this->dashboardService->getAdvisorLeadAssignedData($filters);

        return view('dashboard.main_dashboard', compact(['totalLeadsReceived', 'totalLeadsReceivedEcommerce', 'totalUnAssignedLeadsReceived', 'totalUnAssignedLeadsReceivedEcommerce',
            'teams', 'carAdvisors', 'teamWiseLeadsAssignedAverage', 'totalUnAssignedRevivalLeads', 'leadsCountByTier', 'unAssignedLeadsByTier',
            'revivalLeadsCount', 'advisorLeadsAssignedData', 'assignedLeadsBySource', ]));
    }

    public function getRecentDailyStats(Request $request)
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $startDate = Carbon::parse(explode(',', $request->range)[0])->startOfDay()->format($dateFormat);
        $endDate = Carbon::parse(explode(',', $request->range)[1])->endOfDay()->format($dateFormat);
        $filters = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'teams' => $this->getCurrentUserTeamsAndSubTeams(auth()->user()->id),
            'applyUnAssignedLeadsCountByTierDateFilter' => true,
            'applyTotalUnAssignedLeadsDateFilter' => true,
        ];
        $todaysLeads = CarQuote::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->get();

        $teamWiseLeadsAssignedAverage = $this->dashboardService->getTeamWiseLeadStats($filters);

        $totalLeadsReceived = count($todaysLeads);
        $totalLeadsReceivedEcommerce = count($todaysLeads->where('is_ecommerce', 1));
        $totalUnAssignedLeads = $this->dashboardService->getTotalUnAssignedLeads($filters);
        $totalUnAssignedLeadsReceived = count($totalUnAssignedLeads);
        $totalUnAssignedLeadsReceivedEcommerce = count($totalUnAssignedLeads->where('is_ecommerce', 1));
        $totalUnAssignedRevivalLeads = count($totalUnAssignedLeads->where('source', LeadSourceEnum::REVIVAL));
        $leadsCountByTier = $this->dashboardService->getLeadsCountByTier($filters);
        $revivalLeadsCount = $this->dashboardService->getLeadsCountRevival($filters);
        $unAssignedLeadsByTier = $this->dashboardService->getUnAssignedLeadsCountByTier($filters);
        $advisorLeadsAssignedData = $this->dashboardService->getAdvisorLeadAssignedData($filters);

        return ['totalLeadsReceived' => $totalLeadsReceived, 'totalLeadsReceivedEcommerce' => $totalLeadsReceivedEcommerce, 'totalUnAssignedLeadsReceived' => $totalUnAssignedLeadsReceived,
            'totalUnAssignedLeadsReceivedEcommerce' => $totalUnAssignedLeadsReceivedEcommerce, 'teamWiseLeadsAssignedAverage' => $teamWiseLeadsAssignedAverage,
            'totalUnAssignedRevivalLeads' => $totalUnAssignedRevivalLeads, 'leadsCountByTier' => $leadsCountByTier, 'revivalLeadsCount' => $revivalLeadsCount, 'advisorLeadsAssignedData' => $advisorLeadsAssignedData, 'unAssignedLeadsByTier' => $unAssignedLeadsByTier];
    }

    public function renderTplDashboard(Request $request)
    {
        $tplDashboardStats = $this->getTPLDashboardStats($request);
        $car = $this->getProductByName(quoteTypeCode::Car);
        $teams = $this->getTeamsByProductId($car->id);
        $commonTeams = $this->getCommonTeamsForCurrentUserWithCar();
        $tiers = $this->tierService->getTPLTiers();
        $commonTeam = 0;
        if (count($commonTeams) > 0) {
            $commonTeam = $commonTeams[0];
        }

        return view('dashboard.tpl_dashboard', compact('tplDashboardStats', 'teams', 'commonTeam', 'tiers'));
    }

    public function getTPLDashboardStats(Request $request): array
    {
        $tiers = Tier::where('can_handle_tpl', 1)->where('is_active', 1)->get()->pluck('id');
        $tplTeam = Team::where('name', 'TPL')->where('is_active', 1)->first();
        $records = QuoteBatches::query()
            ->select(
                'quote_batches.id',
                'quote_batches.name',
                DB::raw('DATE_FORMAT(quote_batches.start_date, "%d-%m-%Y") as start_date'),
                DB::raw('DATE_FORMAT(quote_batches.end_date, "%d-%m-%Y") as end_date'),
                DB::raw('SUM(CASE WHEN car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as total_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.'))  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'"  THEN 1 ELSE 0 END) as created_sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
            )
            ->leftJoin('car_quote_request', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('quote_batches.name', 'quote_batches.id')->take(10)->orderBy('quote_batches.start_date', 'desc');

        if (isset($request->tier_filter) && $request->tier_filter != 'undefined') {
            $records->whereIn('tiers.id', $request->tier_filter);
        } else {
            $records->whereIn('tiers.id', $tiers);
        }

        if ($tplTeam != null) {
            $records->whereIn('users.id', function ($query) use ($tplTeam) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->where('teams.id', $tplTeam->id);
            });
        }

        $labels = [];
        $data = [];
        $records = $records->get()->sortBy(function ($record) {
            return $record->id;
        });
        foreach ($records as $record) {
            $numerator = $record->sale_leads - $record->created_sale_leads;
            $denominator = ($record->total_leads - $record->manual_created) - ($record->bad_leads - $record->manual_created_bad_leads);
            $total = $denominator > 0 ? ($numerator / $denominator) : 0;
            $data[] = number_format((float) $total * 100, 2, '.', '');
            $labels[] = $record->name.'-('.$record->start_date.' to '.$record->end_date.')';
        }

        return isset($request->tier_filter) || isset($request->source) ? [json_encode($labels, JSON_OBJECT_AS_ARRAY), json_encode($data, JSON_OBJECT_AS_ARRAY)] : [$labels, $data];
    }

    private function applyFilter($query, $column, $value, $searchType)
    {
        switch ($searchType) {
            case IMCRMSearchTypesEnum::EQUAL_SEARCH :
                $query = $query->where($column, $value);
                break;
            case IMCRMSearchTypesEnum::LIKE_SEARCH :
                $query = $query->where($column, 'like', '%'.$value.'%');
                break;
            case IMCRMSearchTypesEnum::MULTI_SEARCH :
                $query = $query->whereIn($column, $value);
                break;
            case IMCRMSearchTypesEnum::NOT_EQUAL:
                $query = $query->where($column, '!=', $value);
                break;
            case IMCRMSearchTypesEnum::NOT_NULL:
                $query = $query->whereNotNull($column);
                break;
            case IMCRMSearchTypesEnum::NULL:
                $query = $query->whereNull($column);
                break;
            default:
                break;
        }

        return $query;
    }

    public function getComprehensiveDashboardStats(Request $request): array
    {
        $compTiers = Tier::where('can_handle_tpl', 0)->orderBy('name', 'asc')->where('name', '!=', TiersEnum::TIER_R)->where('is_active', 1)->get()->pluck('id');
        $records = CarQuote::query()
            ->select(
                'users.id as advisorId',
                DB::raw('DATE_FORMAT(quote_batches.start_date, "%d-%m-%Y") as start_date'),
                DB::raw('DATE_FORMAT(quote_batches.end_date, "%d-%m-%Y") as end_date'),
                'quote_batches.name as batch_name',
                'users.name as advisor_name',
                'quote_batches.id as quote_batch_id',
                DB::raw('SUM(CASE WHEN car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as total_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as new_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as not_interested'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.','.QuoteStatusEnum::PendingQuote.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.'))  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'"  THEN 1 ELSE 0 END) as created_sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::IMRenewal.' THEN 1 ELSE 0 END)  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" as afia_renewals_count'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
            ->orderByDesc('quote_batches.start_date')->orderBy('users.email');

        if (isset($request->tier_filter) && $request->tier_filter != 'undefined') {
            $records->whereIn('tiers.id', $request->tier_filter);
        } else {
            $records->whereIn('tiers.id', $compTiers);
        }

        if (isset($request->team_filter) && $request->team_filter != 'undefined') {
            $records->whereIn('users.id', function ($query) use ($request) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $request->team_filter);
            });
        } else {
            $commonTeams = $this->getCommonTeamsForCurrentUserWithCar();
            if (count($commonTeams) > 0) {
                $records->whereIn('users.id', function ($query) use ($commonTeams) {
                    $query->distinct()
                        ->select('users.id')
                        ->from('users')
                        ->join('user_team', 'user_team.user_id', 'users.id')
                        ->join('teams', 'teams.id', 'user_team.team_id')
                        ->where('teams.id', $commonTeams[0]);
                });
            }
        }

        if (isset($request->userFilter) && $request->userFilter != 'null') {
            $records = $this->applyFilter($records, 'car_quote_request.advisor_id', $request->userFilter, gettype($request->userFilter) == 'array' ? IMCRMSearchTypesEnum::MULTI_SEARCH : IMCRMSearchTypesEnum::EQUAL_SEARCH);
        }

        $labels = [];
        $data = [];
        $records = $records->get();

        $batchesWiseGroupedData = $records->groupBy('batch_name')->take(10)->sortKeys()->toArray();

        foreach ($batchesWiseGroupedData as $batchData) {
            $saleLeads = 0;
            $createdSaleLeads = 0;
            $totalLeads = 0;
            $badLeads = 0;
            $manualCreatedBadLeads = 0;
            foreach ($batchData as $record) {
                $saleLeads = $saleLeads + $record['sale_leads'];
                $createdSaleLeads = $createdSaleLeads + $record['created_sale_leads'];
                $totalLeads = $totalLeads + $record['total_leads'];
                $badLeads = $badLeads + $record['bad_leads'];
                $manualCreatedBadLeads = $manualCreatedBadLeads + $record['manual_created_bad_leads'];
            }
            $numerator = $saleLeads;
            $denominator = $totalLeads - $badLeads;
            $total = $denominator > 0 ? ($numerator / $denominator) : 0;

            $data[] = number_format((float) $total * 100, 2, '.', '');
            $labels[] = $record['batch_name'].'-('.$record['start_date'].' to '.$record['end_date'].')';
        }

        return isset($request->tier_filter) || isset($request->userFilter) ? [json_encode($labels, JSON_OBJECT_AS_ARRAY), json_encode($data, JSON_OBJECT_AS_ARRAY)] : [$labels, $data];
    }

    public function getCommonTeamsForCurrentUserWithCar()
    {
        $userId = auth()->user()->id;
        $userTeams = $this->getUserTeams($userId)->pluck('id')->toArray();
        info('Inside getCommonTeamsForCurrentUserWithCar user teams are : '.json_encode($userTeams));
        $teamsByProduct = $this->getTeamsByProductName(quoteTypeCode::Car)->pluck('id')->toArray();
        info('Inside getCommonTeamsForCurrentUserWithCar teams by product are : '.json_encode($teamsByProduct));

        return (count($userTeams) > 0 && count($teamsByProduct) > 0) ? array_intersect($userTeams, $teamsByProduct) : [];
    }

    public function renderComprehensiveDashboard(Request $request)
    {
        $carUsers = $this->getUsersByProductName(quoteTypeCode::Car);
        $tiers = Tier::where('can_handle_tpl', 0)->orderBy('name', 'asc')->where('name', '!=', TiersEnum::TIER_R)->where('is_active', 1)->get();
        $comprehensiveDashboardStats = $this->getComprehensiveDashboardStats($request, $tiers);
        info('inside renderComprehensiveDashboard comp stats are : '.json_encode($comprehensiveDashboardStats));
        $teams = $this->getTeamsByProductName(quoteTypeCode::Car);
        $commonTeams = $this->getCommonTeamsForCurrentUserWithCar();
        info('inside renderComprehensiveDashboard common teams are : '.json_encode($commonTeams));
        $commonTeam = 0;
        if (count($commonTeams) > 0) {
            $commonTeam = $commonTeams[0];
        }

        return view('dashboard.comprehensive_dashboard', compact('carUsers', 'tiers', 'comprehensiveDashboardStats', 'teams', 'commonTeam'));
    }

    public function conversionStats($quoteType)
    {
        $statsArray = $this->getWeeklyStats($quoteType);
        $headingArray = $this->getWeeklyHeading();

        return view('dashboard.'.$quoteType.'-conversion', compact('statsArray', 'headingArray'));
    }

    public function getWeeklyStats($type): array
    {
        return [
            '1Week' => $this->dashboardService->getDashboardStatsByDate(
                $this->dashboardService->getPastDateByWeek(0, true),
                $this->dashboardService->getPastDateByWeek(0, false),
                $type
            ),
            '2Week' => $this->dashboardService->getDashboardStatsByDate(
                $this->dashboardService->getPastDateByWeek(1, true),
                $this->dashboardService->getPastDateByWeek(1, false),
                $type
            ),
            '3Week' => $this->dashboardService->getDashboardStatsByDate(
                $this->dashboardService->getPastDateByWeek(2, true),
                $this->dashboardService->getPastDateByWeek(2, false),
                $type
            ),
            '4Week' => $this->dashboardService->getDashboardStatsByDate(
                $this->dashboardService->getPastDateByWeek(3, true),
                $this->dashboardService->getPastDateByWeek(3, false),
                $type
            ),
        ];
    }

    public function getWeeklyHeading(): array
    {
        return [
            '1WeekHeadingDate' => $this->dashboardService->getWeekHeadingDate(0),
            '2WeekHeadingDate' => $this->dashboardService->getWeekHeadingDate(1),
            '3WeekHeadingDate' => $this->dashboardService->getWeekHeadingDate(2),
            '4WeekHeadingDate' => $this->dashboardService->getWeekHeadingDate(3),
        ];
    }

    public function getTeamAdvisorConversionStats(Request $request)
    {

        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $startDate = null;
        $endDate = null;
        if (isset($request->range)) {
            $startDate = Carbon::parse(explode(',', $request->range)[0])->startOfDay()->format($dateFormat);
            $endDate = Carbon::parse(explode(',', $request->range)[1])->endOfDay()->format($dateFormat);
        } else {
            $startDate = now()->startOfDay()->format($dateFormat);
            $endDate = now()->endOfDay()->format($dateFormat);
        }
        $filters = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'teamIds' => $request->teamFilter,
        ];

        return $this->dashboardService->getAdvisorLeadAssignedData($filters);
    }

    public function getUsersByTeam(Request $request)
    {
        return $this->getUsersByTeamId($request->team_filter);
    }
}
