<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TiersEnum;
use App\Models\CarQuote;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function getDashboardStatsByDate($start, $end, $type)
    {
        $tableName = $type.'_quote_request';

        return DB::select('
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

    public function getLeadsCountByTier($filters)
    {
        $query = CarQuote::select(
            'tiers.name as tierNames',
            DB::raw('count(car_quote_request.id) as leadCount')
        )
            ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO])
            ->whereBetween('car_quote_request.created_at', [$filters['startDate'], $filters['endDate']])
            ->groupBy('tiers.id');

        return $query->get();
    }

    public function getUnAssignedLeadsCountByTier($filters)
    {
        $query = CarQuote::select(
            'tiers.name as tierNames',
            DB::raw('count(car_quote_request.id) as leadCount')
        )
            ->leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->whereNull('car_quote_request.advisor_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO])
            ->where('tiers.name', '!=', TiersEnum::TIER_R)
            ->whereNotIn('car_quote_request.uuid', function ($query) { // to remove from the query tags table to exlude SIC records from the result set
                $query->distinct()
                    ->select('quote_uuid')
                    ->from('quote_tags')
                    ->join('quote_type', 'quote_type.id', 'quote_tags.quote_type_id')
                    ->where('quote_tags.name', 'SIC')
                    ->where('quote_type.code', quoteTypeCode::Car);
            })
            ->groupBy('tiers.id');
        if ($filters['applyUnAssignedLeadsCountByTierDateFilter'] == true && $filters['startDate'] != now()->startOfDay()->toDateTimeString()) {
            $query->whereBetween('car_quote_request.created_at', [$filters['startDate'], $filters['endDate']]);
        } else {
            $query->whereBetween('car_quote_request.created_at', [now()->subWeeks(2)->startOfDay(), now()->endOfDay()]);
        }

        return $query->get();
    }

    public function getLeadsCountRevival($filters)
    {
        $query = CarQuote::select(
            DB::raw('sum(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::REVIVAL.'" THEN 1 ELSE 0 END) as revival_leads'),
            DB::raw('sum(CASE WHEN car_quote_request.source != "'.LeadSourceEnum::REVIVAL.'" THEN 1 ELSE 0 END) as non_revival_leads'),
        )
            ->whereBetween('car_quote_request.created_at', [$filters['startDate'], $filters['endDate']])
            ->whereNull('advisor_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO]);

        return $query->get();
    }

    public function getAssignedLeadsCountBySource($filters)
    {
        $query = CarQuote::select(
            DB::raw('distinct(source) as sourceName'),
            DB::raw('count(car_quote_request.id) as sourceCount'),
        )
            ->whereBetween('car_quote_request.created_at', [$filters['startDate'], $filters['endDate']])
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO])
            ->groupBy('source');

        return $query->get();
    }

    public function getAdvisorLeadAssignedData($filters = null)
    {
        $query = CarQuote::select(
            'users.name',
            DB::raw('CAST(COUNT(car_quote_request.id) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as total_leads'),
        )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('user_team', 'users.id', 'user_team.user_id')
            ->join('teams', 'teams.id', 'user_team.team_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO])
            ->where('users.is_active', true)
            ->groupBy('users.id');

        if (isset($filters['startDate']) && isset($filters['endDate'])) {

            $query = $query->whereBetween('car_quote_request_detail.advisor_assigned_date', [$filters['startDate'], $filters['endDate']]);
        }
        if (isset($filters['teamIds'])) {
            $query->whereIn('teams.id', $filters['teamIds']);
        }

        return $query->get();
    }

    public function getTeamWiseLeadStats($filters)
    {
        $leadsData = CarQuote::select('car_quote_request.advisor_id', DB::raw('COUNT(car_quote_request.id) as leads_count'))
            ->join('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'car_quote_request.id')
            ->whereNotNull('car_quote_request.advisor_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO])
            ->whereBetween('cqrd.advisor_assigned_date', [$filters['startDate'], $filters['endDate']])
            ->groupBy('car_quote_request.advisor_id')
            ->get()
            ->keyBy('advisor_id');

        $advisors = $this->getAdvisorsByRole(RolesEnum::CarAdvisor)->groupBy('u_team_id');

        $teamWiseLeadsAssignedAverage = [];

        foreach ($filters['teams'] as $team) {
            $teamUserIds = $advisors->get($team->id, collect())->pluck('id');
            $usersCount = $teamUserIds->count();

            $leadsCount = $teamUserIds->reduce(function ($count, $advisorId) use ($leadsData) {
                return $count + ($leadsData->get($advisorId)->leads_count ?? 0);
            }, 0);

            $stats = $usersCount == 0 ? '0 / 0 = 0.00' : "{$leadsCount} / {$usersCount} = ".number_format($leadsCount / $usersCount, 2);

            $teamWiseLeadsAssignedAverage[] = [
                'totalUsersUnderTeam' => $usersCount,
                'teamName' => $team->name,
                'totalLeadsCount' => $leadsCount,
                'stats' => $stats,
            ];
        }

        return $teamWiseLeadsAssignedAverage;
    }

    public function getTotalUnAssignedLeads($filters, bool $getQuery = false)
    {
        $query = CarQuote::select('is_ecommerce', 'source')
            ->leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->whereNull('advisor_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where('tiers.name', '!=', TiersEnum::TIER_R)
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::REVIVAL, LeadSourceEnum::DUBAI_NOW, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO])
            ->isNonSICLead(QuoteTypes::CAR);

        if ($filters['applyTotalUnAssignedLeadsDateFilter'] == true && $filters['startDate'] != now()->startOfDay()->toDateTimeString()) {
            $query->whereBetween('car_quote_request.created_at', [$filters['startDate'], $filters['endDate']]);
        } else {
            $query->whereBetween('car_quote_request.created_at', [now()->subWeeks(2)->startOfDay(), now()->endOfDay()]);
        }

        if ($getQuery) {
            return $query;
        }

        return $query->get();
    }

    public function getTotalUnAssignedOnlySICLeads($filters, bool $getQuery = false)
    {
        $query = CarQuote::select('payment_status_id')
            ->isSICLead(QuoteTypes::CAR)
            ->whereNull('advisor_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO]);

        if ($filters['applyTotalUnAssignedLeadsDateFilter'] == true && $filters['startDate'] != now()->startOfDay()->toDateTimeString()) {
            $query->whereBetween('car_quote_request.created_at', [$filters['startDate'], $filters['endDate']]);
        } else {
            $query->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->endOfDay()]);
        }

        if ($getQuery) {
            return $query;
        }

        return $query->get();
    }

    public function getStatCounts(array $filters)
    {
        $teamWiseLeadsAssignedAverage = $this->getTeamWiseLeadStats($filters);

        $totalLeadsQuery = CarQuote::whereBetween('created_at', [$filters['startDate'], $filters['endDate']])
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO]);

        $totalLeadsReceived = $totalLeadsQuery->count();
        $totalLeadsReceivedEcommerce = $totalLeadsQuery->where('is_ecommerce', 1)->count();

        $totalUnAssignedLeadsQuery = $this->getTotalUnAssignedLeads($filters, true);
        $totalUnAssignedLeadsReceived = $totalUnAssignedLeadsQuery->count();
        $totalUnAssignedLeadsReceivedEcommerce = $totalUnAssignedLeadsQuery->where('is_ecommerce', 1)->count();
        $totalUnAssignedRevivalLeads = $totalUnAssignedLeadsQuery->where('source', LeadSourceEnum::REVIVAL)->count();

        $totalUnAssignedOnlySICLeadsQuery = $this->getTotalUnAssignedOnlySICLeads($filters, true);
        $totalUnAssignedOnlySICLeadsReceived = $totalUnAssignedOnlySICLeadsQuery->count();
        $totalUnAssignedOnlyPaidSICLeadsReceived = $totalUnAssignedOnlySICLeadsQuery->where('payment_status_id', PaymentStatusEnum::AUTHORISED)->count();

        $leadsCountByTier = $this->getLeadsCountByTier($filters);
        $revivalLeadsCount = $this->getLeadsCountRevival($filters);
        $unAssignedLeadsByTier = $this->getUnAssignedLeadsCountByTier($filters);
        $advisorLeadsAssignedData = $this->getAdvisorLeadAssignedData($filters);

        return [
            'teamWiseLeadsAssignedAverage' => $teamWiseLeadsAssignedAverage,
            'totalLeadsReceived' => $totalLeadsReceived,
            'totalLeadsReceivedEcommerce' => $totalLeadsReceivedEcommerce,
            'totalUnassignedLeadsReceived' => $totalUnAssignedLeadsReceived,
            'totalUnassignedLeadsReceivedEcommerce' => $totalUnAssignedLeadsReceivedEcommerce,
            'totalUnassignedRevivalLeads' => $totalUnAssignedRevivalLeads,
            'totalUnAssignedOnlySICLeadsReceived' => $totalUnAssignedOnlySICLeadsReceived,
            'totalUnAssignedOnlyPaidSICLeadsReceived' => $totalUnAssignedOnlyPaidSICLeadsReceived,
            'leadsCountByTier' => $leadsCountByTier,
            'revivalLeadsCount' => $revivalLeadsCount,
            'unAssignedLeadsByTier' => $unAssignedLeadsByTier,
            'advisorLeadsAssignedData' => $advisorLeadsAssignedData,
        ];
    }
}
