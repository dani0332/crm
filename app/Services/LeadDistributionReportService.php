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

class LeadDistributionReportService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function getReportData($request)
    {
        $query = CarQuote::leftJoin('tiers', 'tiers.id', '=', 'car_quote_request.tier_id')
        ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
        ->select(DB::raw('(

        (SUM(CASE WHEN car_quote_request.auto_assigned = 1 AND car_quote_request.advisor_id IS NOT NULL THEN 1 ELSE 0 END)
        + SUM(CASE WHEN car_quote_request.auto_assigned = 0 AND car_quote_request.advisor_id IS NOT NULL THEN 1 ELSE 0 END)
        + SUM(CASE WHEN car_quote_request.advisor_id IS NULL THEN 1 ELSE 0 END))) AS received_leads,

        SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) AS lead_created, COUNT(*) AS total_leads,

        SUM(CASE WHEN car_quote_request.auto_assigned = 1 AND car_quote_request.advisor_id IS NOT NULL THEN 1 ELSE 0 END) AS auto_assigned,

        SUM(CASE WHEN car_quote_request.auto_assigned = 0 AND car_quote_request.advisor_id IS NOT NULL THEN 1 ELSE 0 END) AS manually_assigned,

        SUM(CASE WHEN car_quote_request.advisor_id IS NULL THEN 1 ELSE 0 END) AS unassigned_leads'), 'tiers.name AS tier_name')
        ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
        ->groupBy('tiers.name');

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
