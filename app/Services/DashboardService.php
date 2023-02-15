<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\CarQuote;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use DB;

class DashboardService extends BaseService
{
    use TeamHierarchyTrait;
    use GetUserTreeTrait;

    public function getDashboardStatsByDate($start, $end, $type)
    {
        $tableName = $type.'_quote_request';
        $stats = DB::select('
                    SELECT *
                    FROM (
                    SELECT
                    count(q.id) total_assigned,
                    SUM(CASE WHEN q.paid_at is not NULL THEN 1 ELSE 0 END) paid_ecom,
                    SUM(CASE WHEN q.paid_at is not NULL AND q.payment_status_id = 4 THEN 1 ELSE 0 END) paid_ecom_auth,
                    SUM(CASE WHEN q.paid_at is not NULL AND q.payment_status_id = 6 THEN 1 ELSE 0 END) paid_ecom_captured,
                    SUM(CASE WHEN q.paid_at is not NULL AND q.payment_status_id = 3 THEN 1 ELSE 0 END) paid_ecom_cancelled,
                    SUM(CASE WHEN q.quote_status_id=15 AND q.is_ecommerce THEN 1 ELSE 0 END) tran_approved_ecom,
                    SUM(CASE WHEN q.quote_status_id=15 AND q.is_ecommerce= 0 THEN 1 ELSE 0 END) tran_approved_non_ecom,
                    SUM(CASE WHEN q.quote_status_id=15 THEN 1 ELSE 0 END) tran_approved_total,
                    SUM(CASE WHEN q.is_ecommerce THEN 1 ELSE 0 END) ecom_total,
                    u.email
                    FROM '.$tableName." q
                    LEFT OUTER JOIN users u on u.id = q.advisor_id
                    WHERE q.quote_status_id NOT IN (9,35)
                    AND q.created_at BETWEEN '".$start."' and '".$end."'
                    AND q.renewal_import_code IS NULL
                    GROUP BY q.advisor_id)  a order by a.email;");

        return $stats;
    }

    public function getPastDateByWeek($noOfWeeksInPast, $startOfWeek)
    {
        $pastDate = Carbon::now()->subWeeks($noOfWeeksInPast);

        return $startOfWeek ? $pastDate->startOfWeek() : $pastDate->endOfWeek();
    }

    public function getWeekHeadingDate($noOfWeeksInPast)
    {
        $pastDate = Carbon::now()->subWeeks($noOfWeeksInPast);

        return 'Week : '.$pastDate->startOfWeek()->format('d M Y').' - '.$pastDate->endOfWeek()->format('d M Y');
    }

    public function getLeadsCountByTier($startDate, $endDate)
    {
        $query = CarQuote::select(
            'tiers.name as tierNames',
            DB::raw('count(*) as leadCount')
        )
        ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
        ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
        ->groupBy('tiers.name');
        if ($startDate == null && $endDate == null) {
            $query->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()]);
        } else {
            $query->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);
        }

        return $query->get();
    }

    public function getUnAssignedLeadsCountByTier($request)
    {
        return
        CarQuote::select(
            'tiers.name as tierNames',
            DB::raw('count(*) as leadCount')
        )
        ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
        ->whereNull('car_quote_request.advisor_id')
        ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
        ->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()])
        ->groupBy('tiers.name')
        ->get();
    }

    public function getLeadsCountRevival($startDate, $endDate)
    {
        $query = CarQuote::select(
            DB::raw('sum(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::REVIVAL.'" THEN 1 ELSE 0 END) as revival_leads'),
            DB::raw('sum(CASE WHEN car_quote_request.source != "'.LeadSourceEnum::REVIVAL.'" THEN 1 ELSE 0 END) as non_revival_leads'),
        )->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
        if ($startDate == null && $endDate == null) {
            $query->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()]);
        } else {
            $query->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);
        }

        return $query->get();
    }

    public function getAssignedLeadsCountBySource($startDate, $endDate)
    {
        $query = CarQuote::select(
            DB::raw('distinct(source) as sourceName'),
            DB::raw('count(*) as sourceCount'),
        )->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])->groupBy('source');
        if ($startDate == null && $endDate == null) {
            $query->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()]);
        } else {
            $query->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);
        }

        return $query->get();
    }

    public function getAdvisorConversionData($advisorId)
    {
        $userIds = $this->walkTree(auth()->user()->id);
        info('user ids for advisor conversion report are : '.json_encode($userIds));
        $query = CarQuote::query()
        ->select(
            'users.id as advisorId',
            DB::raw('count(car_quote_request.id) as total_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' THEN 1 ELSE 0 END) as new_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.') THEN 1 ELSE 0 END) as not_interested'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.') THEN 1 ELSE 0 END) as in_progress'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') THEN 1 ELSE 0 END) as bad_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::TransactionApproved.' THEN 1 ELSE 0 END) as sale_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" and car_quote_request.quote_status_id = '.QuoteStatusEnum::TransactionApproved.' THEN 1 ELSE 0 END) as created_sale_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::AfiaRenewal.' THEN 1 ELSE 0 END) as afia_renewals_count'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id not in (
                '.QuoteStatusEnum::NewLead.','.QuoteStatusEnum::PriceTooHigh.','.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.','.QuoteStatusEnum::NotInterested.',
                '.QuoteStatusEnum::NotEligibleForInsurance.','.QuoteStatusEnum::NotLookingForMotorInsurance.','.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::NotContactablePe.',
                '.QuoteStatusEnum::FollowupCall.','.QuoteStatusEnum::Interested.','.QuoteStatusEnum::NoAnswer.','.QuoteStatusEnum::Quoted.','.QuoteStatusEnum::Duplicate.',
                '.QuoteStatusEnum::Fake.','.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::AfiaRenewal.') THEN 1 ELSE 0 END) as others'),
        )
        ->join('users', 'users.id', 'car_quote_request.advisor_id')
        ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
        ->join('user_team', 'user_team.user_id', 'users.id')
        ->join('teams', 'teams.id', 'user_team.team_id')
        ->whereNull('car_quote_request.renewal_import_code')
        ->whereIn('car_quote_request.advisor_id', $userIds)
        ->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()])
        ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
        ->orderBy('car_quote_request.quote_batch_id')->orderBy('users.email')->take(10);
        if (isset($advisorId)) {
            $query->where('car_quote_request.advisor_id', $advisorId);
        }

        return $query->get();
    }

    public function getAdvisorLeadAssignedData($teamIds)
    {
        $query = CarQuote::select(
            'users.name',
            DB::raw('COUNT(car_quote_request.id) AS total_leads'),
        )
        ->join('users', 'users.id', 'car_quote_request.advisor_id')
        ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
        ->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()])
        ->groupBy('users.name');
        if (isset($teamIds)) {
            $query->whereIn('user_team.team_id', $teamIds);
        }

        return $query->get();
    }

    public function getTeamWiseLeadStats($todaysLeads, $teams)
    {
        $teamWiseLeadsAssignedAverage = [];
        foreach ($teams as $team) {
            $teamUsers = $this->getUsersByTeamId($team->id);
            $teamWiseLeadsAssignedAverage[] = [
                'totalUsersUnderTeam' => count($teamUsers),
                'teamName' => $team->name,
                'totalLeadsCount' => $todaysLeads->whereIn('advisor_id', $teamUsers->pluck('id'))->count(),
            ];
        }

        return $teamWiseLeadsAssignedAverage;
    }
}
