<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Models\Team;
use App\Models\Tier;
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
                DB::raw('count(*) as total_leads'),
                'users.name as advisor_name',
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 0' THEN 1 ELSE 0 END) as tier_0_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 1' THEN 1 ELSE 0 END) as tier_1_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 2' THEN 1 ELSE 0 END) as tier_2_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 3' THEN 1 ELSE 0 END) as tier_3_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 4' THEN 1 ELSE 0 END) as tier_4_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 5' THEN 1 ELSE 0 END) as tier_5_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 6 (non ecom)' THEN 1 ELSE 0 END) as tier_6_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier 6 (Ecom)' THEN 1 ELSE 0 END) as tier_6_lead_count_e"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier L' THEN 1 ELSE 0 END) as tier_l_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier H' THEN 1 ELSE 0 END) as tier_h_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier R' AND tiers.is_active = 1 THEN 1 ELSE 0 END) as tier_r_lead_count"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier TR (Ecom)' AND tiers.is_active = 1 THEN 1 ELSE 0 END) as tier_tr_lead_count_e"),
                DB::raw("SUM(CASE WHEN tiers.name = 'Tier TR (Non ecom)' AND tiers.is_active = 1 THEN 1 ELSE 0 END) as tier_tr_lead_count"),
                DB::raw('SUM(tiers.cost_per_lead) as total_lead_cost'),
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('user_team', 'user_team.user_id', 'users.id')
            ->join('teams', 'teams.id', 'user_team.team_id')
            ->join('tiers', 'tiers.id', 'car_quote_request.tier_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->groupBy('users.email')
            ->orderBy('users.name');
        if (auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
            $query->where('users.id', auth()->user()->id);
        } else {
            if (! auth()->user()->hasRole(RolesEnum::Admin)) {
                $userIds = $this->walkTree(auth()->user()->id);
                info('user ids for advisor conversion report are : '.json_encode($userIds));
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

        return [
            'maxDays' => $maxDays,
            'tiers' => $tiers,
            'teams' => $teams,
        ];
    }

    public function getDefaultFilters()
    {
        $advisorAssignedDates = [
            Carbon::parse(now())->startOfDay()->format('Y-m-d'),
            Carbon::parse(now())->endOfDay()->format('Y-m-d'),
        ];

        return [
            'advisorAssignedDates' => $advisorAssignedDates,
        ];
    }

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $startDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat) :
            Carbon::parse(now())->startOfDay()->format($dateFormat);

        $endDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[1])->endOfDay()->format($dateFormat) :
            Carbon::parse(now())->endOfDay()->format($dateFormat);

        $query->whereBetween('car_quote_request_detail.advisor_assigned_date', [$startDate, $endDate]);

        if (isset($filters->tiers) && count($filters->tiers) > 0) {
            info('tiersFilter are : '.json_encode($filters->tiers));
            $query->whereIn('car_quote_request.tier_id', $filters->tiers);
        }
        if (isset($filters->teams) && count($filters->teams) > 0) {
            info('teamsFilter are : '.json_encode($filters->teams));
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
        return $query;
    }
}
