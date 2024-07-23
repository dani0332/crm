<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\TiersEnum;
use App\Models\ApplicationStorage;
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

    public function getLeadsCountByTier($filters)
    {
        $query = CarQuote::select(
            'tiers.name as tierNames',
            DB::raw('count(*) as leadCount')
        )
            ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->whereBetween('car_quote_request.created_at', [$filters['startDate'], $filters['endDate']])
            ->groupBy('tiers.name');

        return $query->get();
    }

    public function getUnAssignedLeadsCountByTier($filters)
    {
        $query = CarQuote::select(
            'tiers.name as tierNames',
            DB::raw('count(*) as leadCount')
        )
            ->leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->whereNull('car_quote_request.advisor_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->where('tiers.name', '!=', TiersEnum::TIER_R)
            ->whereNotIn('car_quote_request.uuid', function ($query) { // to remove from the query tags table to exlude SIC records from the result set
                $query->distinct()
                    ->select('quote_uuid')
                    ->from('quote_tags')
                    ->join('quote_type', 'quote_type.id', 'quote_tags.quote_type_id')
                    ->where('quote_tags.name', 'SIC')
                    ->where('quote_type.code', quoteTypeCode::Car);
            })
            ->groupBy('tiers.name');
        if ($filters['applyUnAssignedLeadsCountByTierDateFilter'] == true && $filters['startDate'] != now()->startOfDay()->toDateTimeString()) {
            $query->whereBetween('car_quote_request.created_at', [$filters['startDate'], $filters['endDate']]);
        } else {
            $from = ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_START_DATE_FOR_LEADS')->first()->value;
            $query->whereBetween('car_quote_request.created_at', [$from, now()->endOfDay()]);
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
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD]);

        return $query->get();
    }

    public function getAssignedLeadsCountBySource($filters)
    {
        $query = CarQuote::select(
            DB::raw('distinct(source) as sourceName'),
            DB::raw('count(*) as sourceCount'),
        )
            ->whereBetween('car_quote_request.created_at', [$filters['startDate'], $filters['endDate']])
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
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
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->where('users.is_active', true)
            ->groupBy('users.name');

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
        $todaysLeads = CarQuote::join('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'car_quote_request.id')
            ->whereNotNull('car_quote_request.advisor_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->whereBetween('cqrd.advisor_assigned_date', [$filters['startDate'], $filters['endDate']])->get();

        $teamWiseLeadsAssignedAverage = [];
        foreach ($filters['teams'] as $team) {
            $teamUserIds = $this->getAdvisorsByTeamId($team->id)->pluck('id');
            $usersCount = count($teamUserIds);

            $leadsCount = $todaysLeads->whereIn('advisor_id', array_unique($teamUserIds->toArray()))->count();
            $stats = $leadsCount.' / '.$usersCount.' =  '.number_format((float) $usersCount == 0 ? 0 : $leadsCount / $usersCount, 2, '.', '');

            $teamWiseLeadsAssignedAverage[] = [
                'totalUsersUnderTeam' => $usersCount,
                'teamName' => $team->name,
                'totalLeadsCount' => $leadsCount,
                'stats' => $stats,
            ];
        }

        return $teamWiseLeadsAssignedAverage;
    }

    public function getTotalUnAssignedLeads($filters)
    {
        $query = CarQuote::select('is_ecommerce', 'source')
            ->leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->whereNull('advisor_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where('tiers.name', '!=', TiersEnum::TIER_R)
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::REVIVAL, LeadSourceEnum::DUBAI_NOW])
            ->whereNotIn('car_quote_request.uuid', function ($query) { // to remove from the query tags table to exlude SIC records from the result set
                $query->distinct()
                    ->select('quote_uuid')
                    ->from('quote_tags')
                    ->where('quote_tags.name', QuoteSegmentEnum::SIC->tag())
                    ->where('quote_tags.quote_type_id', QuoteTypeId::Car);
            });

        if ($filters['applyTotalUnAssignedLeadsDateFilter'] == true && $filters['startDate'] != now()->startOfDay()->toDateTimeString()) {
            $query->whereBetween('car_quote_request.created_at', [$filters['startDate'], $filters['endDate']]);
        } else {
            $from = ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_START_DATE_FOR_LEADS')->first()->value;
            $query->whereBetween('car_quote_request.created_at', [$from, now()->endOfDay()]);
        }

        return $query->get();
    }

    public function getTotalUnAssignedOnlySICLeads($filters, $isPaid = false)
    {
        $query = CarQuote::leftJoin('quote_tags', 'quote_tags.quote_uuid', 'car_quote_request.uuid')
            ->whereNull('advisor_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD]);

        if ($filters['applyTotalUnAssignedLeadsDateFilter'] == true && $filters['startDate'] != now()->startOfDay()->toDateTimeString()) {
            $query->whereBetween('car_quote_request.created_at', [$filters['startDate'], $filters['endDate']]);
        } else {
            $from = ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_START_DATE_FOR_LEADS')->first()->value;
            if (! $isPaid) {
                $from = now()->startOfDay();
            }
            $query->whereBetween('car_quote_request.created_at', [$from, now()->endOfDay()]);
        }

        // fetch only records with SIC tag
        $query = $query->where('quote_tags.name', 'SIC');

        // fetch only paid records if isPaid is true
        if ($isPaid) {
            $query = $query->where('car_quote_request.payment_status_id', PaymentStatusEnum::AUTHORISED);
        }

        return $query->get();
    }
}
