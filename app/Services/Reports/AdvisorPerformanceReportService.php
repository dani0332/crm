<?php

namespace App\Services\Reports;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
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

class AdvisorPerformanceReportService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function getReportData($request)
    {
        $query = CarQuote::query()
            ->select(
                'users.name as advisor_name',
                DB::raw('count(DISTINCT car_quote_request.id) as total_leads'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as new_leads'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.assignment_type in (1,2) THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as auto_assigned'),
                DB::raw('CAST(SUM(CASE WHEN (car_quote_request.assignment_type in (3,4) and source != "'.LeadSourceEnum::IMCRM.'" ) THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as manually_assigned'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.') THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as not_interested'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.','.QuoteStatusEnum::PendingQuote.') THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as in_progress'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as manual_created'),
                DB::raw('CAST(SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as bad_leads'),
                DB::raw('CAST(SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED) as sale_leads'),
                DB::raw('IFNULL(CAST(SUM(quote_view_count.visit_count) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED), 0) as view_count'),
            )
            ->filterBySegment()
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->leftJoin('quote_view_count', 'quote_view_count.quote_id', 'car_quote_request.id')
            ->join('user_team', 'user_team.user_id', 'users.id')
            ->join('teams', 'teams.id', 'user_team.team_id')
            ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->where('users.is_active', true)
            ->groupBy('car_quote_request.advisor_id')
            ->orderBy('users.email');

        if (! auth()->user()->hasRole(RolesEnum::LeadPool) && ! auth()->user()->can(PermissionsEnum::VIEW_ALL_REPORTS)) {
            $userIds = $this->walkTree(auth()->user()->id);
            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
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
            ->where('is_active', 1)
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
        if (isset($filters->assignmentTypes) && $filters->assignmentTypes != 'All') {
            $query->where('car_quote_request.assignment_type', $filters->assignmentTypes);
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

        if (isset($filters->leadSourceFilter) && count($filters->leadSourceFilter) > 0) {
            $query->whereIn('car_quote_request.source', $filters->leadSourceFilter);
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
