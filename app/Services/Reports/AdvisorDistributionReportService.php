<?php

namespace App\Services\Reports;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\Team;
use App\Models\Tier;
use App\Services\ApplicationStorageService;
use App\Services\BaseService;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdvisorDistributionReportService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function getReportData($request)
    {
        $query = CarQuote::query()
            ->select(
                DB::raw('count(DISTINCT car_quote_request.id) as total_leads'),
                'users.name as advisor_name',
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier 0' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_0_lead_count"),
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier 1' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_1_lead_count"),
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier 2' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_2_lead_count"),
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier 3' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_3_lead_count"),
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier 4' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_4_lead_count"),
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier 5' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_5_lead_count"),
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier 6 (non ecom)' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_6_lead_count"),
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier 6 (Ecom)' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_6_lead_count_e"),
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier L' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_l_lead_count"),
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier H' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_h_lead_count"),
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier R' AND tiers.is_active = 1 THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_r_lead_count"),
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier TR (Ecom)' AND tiers.is_active = 1 THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_tr_lead_count_e"),
                DB::raw("CAST(SUM(CASE WHEN tiers.name = 'Tier TR (Non ecom)' AND tiers.is_active = 1 THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as tier_tr_lead_count"),
                DB::raw('CAST(SUM(tiers.cost_per_lead) / COUNT(DISTINCT(user_team.team_id))  AS UNSIGNED) as total_lead_cost'),
            )
            ->filterBySegment()
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('user_team', 'user_team.user_id', 'users.id')
            ->join('teams', 'teams.id', 'user_team.team_id')
            ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->where('users.is_active', true)
            ->groupBy('users.email')
            ->orderBy('users.name');
        if (auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
            $query->where('users.id', auth()->user()->id);
        } else {
            if (! auth()->user()->hasRole(RolesEnum::LeadPool)) {
                $userIds = $this->walkTree(auth()->user()->id);
                $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
            }
        }

        $query = $this->applyFilters($query, $request->all());

        return $query->paginate(15)
            ->withQueryString();
    }

    public function getFilterOptions()
    {
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $loginUserId = auth()->user()->id;
        $teamIds = $this->getUserTeams($loginUserId);
        $teams = Team::whereIn('id', $teamIds->pluck('id'))
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();

        $tiers = Tier::query()
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();

        $leadSources = LeadSource::query()
            ->select('name')
            ->where('is_active', 1)->where('is_applicable_for_rules', 0)
            ->whereNotNull('name')
            ->orderBy('name')
            ->get()
            ->keyBy('name')
            ->map(fn ($users) => $users->name)
            ->toArray();

        return [
            'maxDays' => $maxDays,
            'tiers' => $tiers,
            'teams' => $teams,
            'leadSources' => $leadSources,
        ];
    }

    public function getDefaultFilters()
    {
        $dateFormat = config('constants.DATE_FORMAT_ONLY');
        $advisorAssignedDates = [
            Carbon::parse(now())->startOfDay()->format($dateFormat),
            Carbon::parse(now())->endOfDay()->format($dateFormat),
        ];

        return [
            'advisorAssignedDates' => $advisorAssignedDates,
        ];
    }

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        $startDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat) :
                ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) : Carbon::parse(now()->subDays($maxDays))->startOfDay()->format($dateFormat));

        $endDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[1])->endOfDay()->format($dateFormat) : Carbon::parse(now())->endOfDay()->format($dateFormat);

        $query->whereBetween('car_quote_request_detail.advisor_assigned_date', [$startDate, $endDate]);

        if (isset($filters->tiers) && count($filters->tiers) > 0) {
            $query->whereIn('car_quote_request.tier_id', $filters->tiers);
        }
        if (isset($filters->teams) && count($filters->teams) > 0) {
            $value = $filters->teams;
            $query->whereIn('users.id', function ($query) use ($value) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $value);
            });
        }

        if (isset($filters->isCommercial) && $filters->isCommercial != 'All') {
            $filters->isCommercial = $filters->isCommercial == 'true' ? true : false;
            $query->where('car_model.is_commercial', '=', $filters->isCommercial);
        }

        if (isset($filters->leadSources) && count($filters->leadSources) > 0) {
            $query->whereIn('car_quote_request.source', $filters->leadSources);
        }

        return $query;
    }
}
