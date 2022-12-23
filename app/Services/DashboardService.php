<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Models\CarQuote;
use App\Traits\TeamHierarchyHelpers;
use Carbon\Carbon;
use DB;

class DashboardService extends BaseService
{
    use TeamHierarchyHelpers;

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
        ->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()])
        ->groupBy('tiers.name')
        ->get();
    }

    public function getLeadsCountRevival($startDate, $endDate)
    {
        $query = CarQuote::select(
            DB::raw('sum(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::REVIVAL.'" THEN 1 ELSE 0 END) as revival_leads'),
            DB::raw('sum(CASE WHEN car_quote_request.source != "'.LeadSourceEnum::REVIVAL.'" THEN 1 ELSE 0 END) as non_revival_leads'),
        );
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
        );
        if ($startDate == null && $endDate == null) {
            $query->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()]);
        } else {
            $query->whereBetween('car_quote_request.created_at', [$startDate, $endDate]);
        }

        return $query->get();
    }

    public function getAdvisorConversionData($advisorId)
    {
        $query = CarQuote::select(
            'quote_batches.name',
            'quote_batches.start_date',
            'quote_batches.end_date',
            DB::raw('COUNT(car_quote_request.id) AS total_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM" THEN 1 ELSE 0 END) AS manual_created'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id IN (9, 35) THEN 1 ELSE 0 END) AS bad_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = 33 THEN 1 ELSE 0 END) AS sale_leads'),
            DB::raw('SUM(CASE WHEN car_quote_request.source = "IMCRM"
            AND car_quote_request.quote_status_id = 15 THEN 1 ELSE 0 END) AS created_sale_leads'),
        )
        ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
        ->groupBy('quote_batches.name')
        ->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()])
        ->orderBy('quote_batches.id', 'desc')
        ->take(10);
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
        ->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()])
        ->groupBy('users.name');
        if (isset($teamIds)) {
            $userIds = $this->getUsersByTeamIds($teamIds)->pluck('user_id');
            $query->whereIn('users.id', $userIds);
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
