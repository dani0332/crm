<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\QuoteBatches;
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
        $query = QuoteBatches::query()
        ->select(
            'users.id as advisorId',
            'quote_batches.id',
            'quote_batches.name',
            DB::raw('DATE_FORMAT(quote_batches.start_date, "%d-%m-%Y") as start_date'),
            DB::raw('DATE_FORMAT(quote_batches.end_date, "%d-%m-%Y") as end_date'),
            DB::raw('count(car_quote_request.id) as total_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' THEN 1 ELSE 0 END) as new_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.') THEN 1 ELSE 0 END) as not_interested'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.') THEN 1 ELSE 0 END) as in_progress'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') THEN 1 ELSE 0 END) as bad_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id  in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.') THEN 1 ELSE 0 END) as sale_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" and car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.') THEN 1 ELSE 0 END) as created_sale_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::IMRenewal.' THEN 1 ELSE 0 END) as afia_renewals_count'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
        )
        ->join('car_quote_request', 'car_quote_request.quote_batch_id', 'quote_batches.id')
        ->join('users', 'users.id', 'car_quote_request.advisor_id')
        ->join('user_team', 'user_team.user_id', 'users.id')
        ->join('teams', 'teams.id', 'user_team.team_id')
        ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
        ->whereNull('car_quote_request.renewal_import_code')
        ->groupBy('quote_batches.id')->skip(0)->take(10)->orderBy('quote_batches.id', 'desc');

        if (isset($advisorId)) {
            $query->where('car_quote_request.advisor_id', $advisorId);
        }
        if (! auth()->user()->hasRole(RolesEnum::Admin)) {
            $userIds = $this->walkTree(auth()->user()->id);
            info('user ids for advisor conversion report are : '.json_encode($userIds));
            $query = $query->whereIn('teams.id', $userIds);
        }

        $query = $query->get()->sortBy(function ($record) {
            return $record->id;
        });
        $data = [];
        $labels = [];
        foreach ($query as $record) {
            $numerator = $record->sale_leads - $record->created_sale_leads;
            $denominator = ($record->total_leads - $record->manual_created) - ($record->bad_leads - $record->manual_created_bad_leads);
            $total = $denominator > 0 ? ($numerator / $denominator) : 0;
            $data[] = number_format((float) $total * 100, 2, '.', '');
            $labels[] = $record->name.'-('.$record->start_date.' to '.$record->end_date.')';
        }

        return [$labels, $data];
    }

    public function getAdvisorLeadAssignedData($teamIds)
    {
        $query = CarQuote::select(
            'users.name',
            DB::raw('COUNT(car_quote_request.id) AS total_leads'),
        )
        ->join('users', 'users.id', 'car_quote_request.advisor_id')
        ->join('user_team', 'users.id', 'user_team.user_id')
        ->join('teams', 'teams.id', 'user_team.team_id')
        ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
        ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])

        ->whereBetween('car_quote_request_detail.advisor_assigned_date', [now()->startOfDay(), now()->endOfDay()])
        ->groupBy('users.name');
        if (isset($teamIds)) {
            $query->whereIn('teams.id', $teamIds);
        }
        if (! auth()->user()->hasRole(RolesEnum::Admin)) {
            $userIds = $this->walkTree(auth()->user()->id);
            info('user ids for advisor conversion report are : '.json_encode($userIds));
            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        }

        return $query->get();
    }

    public function getTeamWiseLeadStats($todaysLeads, $teams)
    {
        $teamWiseLeadsAssignedAverage = [];
        foreach ($teams as $team) {
            $teamUserIds = $this->getUsersByTeamId($team->id)->pluck('id');

            $usersCount = count($teamUserIds);
            $leadsCount = $todaysLeads->whereIn('advisor_id', $teamUserIds)->count();

            $teamWiseLeadsAssignedAverage[] = [
                'totalUsersUnderTeam' => $usersCount,
                'teamName' => $team->name,
                'totalLeadsCount' => $leadsCount,
            ];
        }

        return $teamWiseLeadsAssignedAverage;
    }
}
