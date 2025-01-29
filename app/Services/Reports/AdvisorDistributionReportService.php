<?php

namespace App\Services\Reports;

use App\Enums\EmbeddedProductEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Enums\TravelQuoteEnum;
use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\PersonalQuote;
use App\Models\Tier;
use App\Models\UserManager;
use App\Repositories\QuoteTypeRepository;
use App\Services\ApplicationStorageService;
use App\Services\BaseService;
use App\Services\DropdownSourceService;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdvisorDistributionReportService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function getReportData($request)
    {
        $lobs = $this->getLobByPermissions();
        $lob = $request->lob ?? (count($lobs) == 1 ? reset($lobs) : '');
        if (empty($lob)) {
            return [];
        }

        if ($lob === quoteTypeCode::Car) {
            $query = $this->getCarQuoteQuery();
            $query = $this->applyFiltersForCar($query, $request->all());
        } else {
            $query = $this->getPersonsalQuoteQuery($lob);
            $query = $this->applyFilters($query, $request->all());
        }

        $query->when(
            $request->assignmentType && strtolower($request->assignmentType) !== 'all',
            fn ($q) => $q->where('assignment_type', $request->assignmentType)
        );

        return $query->paginate(15)->withQueryString();
    }

    private function getCarQuoteQuery()
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

        if (
            ! auth()->user()->hasAnyRole([
                RolesEnum::LeadPool,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ])
            &&
            ! auth()->user()->can(PermissionsEnum::VIEW_ALL_REPORTS)
        ) {
            if (auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
                $query = $query->where('users.id', auth()->user()->id);
            } else {
                $userIds = $this->walkTree(auth()->user()->id);
                $userIds = UserManager::where('manager_id', auth()->user()->id)
                    ->get()
                    ->filter(function ($user) use ($userIds) {
                        return in_array($user->user_id, $userIds);
                    })
                    ->pluck('user_id')
                    ->toArray();
                $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
            }
        }

        return $query;
    }

    private function getPersonsalQuoteQuery($lob)
    {
        $lobFiltered = in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE]) ? quoteTypeCode::Business : $lob;
        $lobId = QuoteTypeRepository::where('code', $lobFiltered)->first();

        $selectColumns = [
            DB::raw('count(DISTINCT personal_quotes.id) as total_leads'),
            'users.name as advisor_name',
        ];
        $query = PersonalQuote::query()
            ->select($selectColumns)
            ->join('users', 'users.id', 'personal_quotes.advisor_id')
            ->join('personal_quote_details', 'personal_quote_details.personal_quote_id', 'personal_quotes.id')
            ->whereNotIn('personal_quotes.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where('personal_quotes.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->where('personal_quotes.quote_type_id', $lobId->id)
            ->where('users.is_active', true)
            ->groupBy('users.email')
            ->orderBy('users.name');

        if (in_array($lob, [quoteTypeCode::Car])) {
            $query = $query->select(
                array_merge(
                    $selectColumns,
                    [
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
                    ]
                )
            )
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', 'user_team.team_id')
                ->join('tiers', 'tiers.id', 'personal_quotes.tier_id');
        }

        if (
            ! auth()->user()->hasAnyRole([
                RolesEnum::LeadPool,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ])
            &&
            ! auth()->user()->can(PermissionsEnum::VIEW_ALL_REPORTS)
        ) {
            if (auth()->user()->isAdvisor()) {
                $query = $query->where('users.id', auth()->user()->id);
            } else {
                $userIds = $this->walkTree(auth()->user()->id, $lob);
                $userIds = UserManager::where('manager_id', auth()->user()->id)
                    ->get()
                    ->filter(function ($user) use ($userIds) {
                        return in_array($user->user_id, $userIds);
                    })
                    ->pluck('user_id')
                    ->toArray();
                $query = $query->whereIn('personal_quotes.advisor_id', $userIds);
            }
        }

        return $query;
    }

    public function getFiltersByLob()
    {
        $canView = [
            quoteTypeCode::Car => ! Auth::user()->hasRole(RolesEnum::CarAdvisor),
            quoteTypeCode::Bike => ! Auth::user()->hasRole(RolesEnum::BikeAdvisor),
            quoteTypeCode::Health => ! Auth::user()->hasRole(RolesEnum::RMAdvisor),
            quoteTypeCode::Travel => ! Auth::user()->hasRole(RolesEnum::TravelAdvisor),
            quoteTypeCode::Pet => ! Auth::user()->hasRole(RolesEnum::PetAdvisor),
            quoteTypeCode::Cycle => ! Auth::user()->hasRole(RolesEnum::CycleAdvisor),
            quoteTypeCode::Yacht => ! Auth::user()->hasRole(RolesEnum::YachtAdvisor),
            quoteTypeCode::Life => ! Auth::user()->hasRole(RolesEnum::LifeAdvisor),
            quoteTypeCode::Home => ! Auth::user()->hasRole(RolesEnum::HomeAdvisor),
            quoteTypeCode::CORPLINE => ! Auth::user()->hasRole(RolesEnum::CorpLineAdvisor),
            quoteTypeCode::GroupMedical => ! Auth::user()->hasRole(RolesEnum::GMAdvisor),
        ];

        return [
            'advisors' => [
                'can_view' => $canView,
            ],
            'teams' => [
                'can_view' => $canView,
                'lobs' => [
                    quoteTypeCode::Car,
                    quoteTypeCode::Health,
                    quoteTypeCode::Travel,
                    quoteTypeCode::Life,
                    quoteTypeCode::Home,
                    quoteTypeCode::Pet,
                    quoteTypeCode::Cycle,
                    quoteTypeCode::Yacht,
                    quoteTypeCode::CORPLINE,
                    quoteTypeCode::GroupMedical,
                ],
            ],
            'sub_teams' => [
                'can_view' => $canView,
                'lobs' => [
                    quoteTypeCode::Car,
                    quoteTypeCode::GroupMedical,
                ],
            ],
            'tiers' => [
                'lobs' => [
                    quoteTypeCode::Car,
                    quoteTypeCode::Bike,
                ],
            ],
            'isCommercial' => [
                'lobs' => [
                    quoteTypeCode::Car,
                ],
            ],
            'isEmbeddedProducts' => [
                'lobs' => [
                    quoteTypeCode::Travel,
                ],
            ],
            'insurance_type' => [
                'lobs' => [
                    quoteTypeCode::Travel,
                    quoteTypeCode::Life,
                    quoteTypeCode::CORPLINE,
                ],
            ],
            'insurance_for' => [
                'lobs' => [
                    quoteTypeCode::Health,
                    quoteTypeCode::Home,
                ],
            ],
            'travel_coverage' => [
                'lobs' => [
                    quoteTypeCode::Travel,
                ],
            ],
            'segment_filter' => [
                'lobs' => [
                    quoteTypeCode::Car,
                ],
            ],
        ];
    }

    public function getLobByPermissions()
    {
        $lobs = [
            quoteTypeCode::Car => PermissionsEnum::ADVISOR_DISTRIBUTION_REPORT_VIEW,
            quoteTypeCode::Bike => PermissionsEnum::BIKE_DISTRIBUTION_REPORT,
            quoteTypeCode::Health => PermissionsEnum::HEALTH_DISTRIBUTION_REPORT,
            quoteTypeCode::Travel => PermissionsEnum::TRAVEL_DISTRIBUTION_REPORT,
            quoteTypeCode::Pet => PermissionsEnum::PET_DISTRIBUTION_REPORT,
            quoteTypeCode::Cycle => PermissionsEnum::CYCLE_DISTRIBUTION_REPORT,
            quoteTypeCode::Yacht => PermissionsEnum::YACHT_DISTRIBUTION_REPORT,
            quoteTypeCode::Life => PermissionsEnum::LIFE_DISTRIBUTION_REPORT,
            quoteTypeCode::Home => PermissionsEnum::HOME_DISTRIBUTION_REPORT,
        ];

        $lobs = array_filter($lobs, function ($permission, $lob) {
            return Auth::user()->can($permission) || (Auth::user()->can(PermissionsEnum::VIEW_ALL_REPORTS) && userHasProduct($lob));
        }, ARRAY_FILTER_USE_BOTH);

        $lobs = QuoteTypeRepository::GetList()
            ->filter(function ($lob) use ($lobs) {
                return array_key_exists($lob->code, $lobs);
            })
            ->pluck('code', 'text')
            ->toArray();

        if (Auth::user()->can(PermissionsEnum::CORPLINE_DISTRIBUTION_REPORT) || (Auth::user()->can(PermissionsEnum::VIEW_ALL_REPORTS) && userHasProduct(quoteTypeCode::CORPLINE))) {
            $lobs = array_merge(['CorpLine Insurance' => quoteTypeCode::CORPLINE], $lobs);
        }

        if (Auth::user()->can(PermissionsEnum::GROUPMEDICAL_DISTRIBUTION_REPORT) || (Auth::user()->can(PermissionsEnum::VIEW_ALL_REPORTS) && userHasProduct(quoteTypeCode::GroupMedical))) {
            $lobs = array_merge(['Group Medical Insurance' => quoteTypeCode::GroupMedical], $lobs);
        }

        return $lobs;
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

        $leadSources = LeadSource::query()
            ->select('name')
            ->where('is_active', 1)
            ->whereNotNull('name')
            ->orderBy('name')
            ->get()
            ->keyBy('name')
            ->map(fn ($users) => $users->name)
            ->toArray();

        $lobs = $this->getLobByPermissions();
        $dropdownSourceService = new DropdownSourceService;

        $insuranceFor = [
            quoteTypeCode::Health => $dropdownSourceService->getDropdownSource('cover_for_id'),
            quoteTypeCode::Home => $dropdownSourceService->getDropdownSource('iam_possesion_type_id'),
        ];

        $travelCoverage = [
            quoteTypeCode::Travel => [
                TravelQuoteEnum::TRAVEL_UAE_INBOUND => [
                    ['value' => TravelQuoteEnum::COVERAGE_CODE_SINGLE_TRIP, 'label' => 'Single Trip'],
                    ['value' => TravelQuoteEnum::COVERAGE_CODE_MULTI_TRIP, 'label' => 'Multi Trip'],
                ],
                TravelQuoteEnum::TRAVEL_UAE_OUTBOUND => [
                    ['value' => TravelQuoteEnum::COVERAGE_CODE_SINGLE_TRIP, 'label' => 'Single Trip'],
                    ['value' => TravelQuoteEnum::COVERAGE_CODE_ANNUAL_TRIP, 'label' => 'Annual Trip'],
                ],
            ],
        ];

        $lifeInsuranceType = $dropdownSourceService->getDropdownSource('tenure_of_insurance_id')->map(function ($type) {
            return ['value' => $type['id'], 'label' => $type['text']];
        })->toArray();
        $businessInsuranceType = $dropdownSourceService->getDropdownSource('business_type_of_insurance_id')
            ->filter(function ($type) {
                return $type['text'] != quoteBusinessTypeCode::groupMedical;
            })
            ->map(function ($type) {
                return ['value' => $type['id'], 'label' => $type['text']];
            })
            ->toArray();
        $businessInsuranceType = array_values($businessInsuranceType);
        $insuranceType = [
            quoteTypeCode::Travel => [
                ['value' => TravelQuoteEnum::TRAVEL_UAE_INBOUND, 'label' => 'To the UAE (Inbound)'],
                ['value' => TravelQuoteEnum::TRAVEL_UAE_OUTBOUND, 'label' => 'Outside UAE (OutBound)'],
            ],
            quoteTypeCode::Life => $lifeInsuranceType,
            quoteTypeCode::CORPLINE => $businessInsuranceType,
        ];

        return [
            'lob' => $lobs,
            'maxDays' => $maxDays,
            'tiers' => $tiers,
            'leadSources' => $leadSources,
            'insurance_for' => $insuranceFor,
            'travel_coverage' => $travelCoverage,
            'insurance_type' => $insuranceType,
        ];
    }

    public function getDefaultFilters()
    {
        $dateFormat = config('constants.DATE_FORMAT_ONLY');
        $advisorAssignedDates = [
            Carbon::parse(now())->startOfDay()->format($dateFormat),
            Carbon::parse(now())->endOfDay()->format($dateFormat),
        ];
        $lobs = $this->getLobByPermissions();

        $isEmbeddedProducts = false;

        return [
            'lob' => count($lobs) == 1 ? reset($lobs) : '',
            'advisorAssignedDates' => $advisorAssignedDates,
            'isCommercial' => 'All',
            'isEmbeddedProducts' => $isEmbeddedProducts,
        ];
    }

    private function applyFiltersForCar($query, $filters)
    {
        $filters = (object) $filters;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        $startDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat) : ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) : Carbon::parse(now()->subDays($maxDays))->startOfDay()->format($dateFormat));

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

        if ((isset($filters->sub_teams) && count($filters->sub_teams) > 0)) {
            $value = $filters->sub_teams;
            $query->whereIn('users.id', function ($query) use ($value) {

                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->whereIn('sub_team_id', $value);
            });
        }

        if (isset($filters->advisors) && count($filters->advisors) > 0) {
            $query->whereIn('car_quote_request.advisor_id', $filters->advisors);
        }

        if (isset($filters->isCommercial) && $filters->isCommercial != 'All') {
            $filters->isCommercial = $filters->isCommercial == 'true' ? true : false;
            $query->where('car_model.is_commercial', '=', $filters->isCommercial);
        }

        if (isset($filters->leadSources) && count($filters->leadSources) > 0) {
            $query->whereIn('car_quote_request.source', $filters->leadSources);
        }
        if (isset($filters->sic_advisor_requested) && $filters->sic_advisor_requested != 'All') {
            $query->where('car_quote_request.sic_advisor_requested', '=', $filters->sic_advisor_requested);
        }

        if (isset($filters->segment_filter) && $filters->segment_filter != 'all') {
            $query = $query->filterBySegment($filters->segment_filter, QuoteTypeId::Car);
        }

        return $query;
    }

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $lob = $filters->lob ?? '';

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        $startDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat) : ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) : Carbon::parse(now()->subDays($maxDays))->startOfDay()->format($dateFormat));

        $endDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[1])->endOfDay()->format($dateFormat) : Carbon::parse(now())->endOfDay()->format($dateFormat);

        $query->whereBetween('personal_quote_details.advisor_assigned_date', [$startDate, $endDate]);

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

        if ((isset($filters->sub_teams) && count($filters->sub_teams) > 0)) {
            $value = $filters->sub_teams;
            $query->whereIn('users.id', function ($query) use ($value) {

                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->whereIn('sub_team_id', $value);
            });
        }

        if (isset($filters->advisors) && count($filters->advisors) > 0) {
            $query->whereIn('personal_quotes.advisor_id', $filters->advisors);
        }

        if (isset($filters->leadSources) && count($filters->leadSources) > 0) {
            $query->whereIn('personal_quotes.source', $filters->leadSources);
        }

        if ($lob === quoteTypeCode::Car) {

            if (isset($filters->tiers) && count($filters->tiers) > 0) {
                $query->whereIn('personal_quotes.tier_id', $filters->tiers);
            }

            if (isset($filters->isCommercial) && $filters->isCommercial != 'All') {
                $filters->isCommercial = $filters->isCommercial == 'true' ? true : false;
                $query->join('car_quote_request', 'car_quote_request.uuid', 'personal_quotes.uuid');
                $query->leftJoin('car_model', function ($join) use ($filters) {
                    $join->on('car_model.id', 'car_quote_request.car_model_id')
                        ->where('car_model.is_commercial', $filters->isCommercial);
                });
            }

            if (isset($filters->segment_filter) && $filters->segment_filter != 'all') {
                $query = $query->filterBySegment($filters->segment_filter, QuoteTypeId::Car);
            }
        }

        if ($lob === quoteTypeCode::Health) {

            if (isset($filters->sic_advisor_requested) && $filters->sic_advisor_requested != 'All') {

                $query->join('health_quote_request', function ($join) use ($filters) {
                    $join->on('health_quote_request.uuid', 'personal_quotes.uuid')
                        ->where('health_quote_request.sic_advisor_requested', $filters->sic_advisor_requested);
                });
            }

            if (! empty($filters->insurance_for) && $filters->insurance_for != '') {
                $query->join('health_quote_request', function ($join) use ($filters) {
                    $join->on('health_quote_request.uuid', 'personal_quotes.uuid')
                        ->where('health_quote_request.cover_for_id', $filters->insurance_for);
                });
            }
        }

        if ($lob === quoteTypeCode::Home) {
            if (! empty($filters->insurance_for) && $filters->insurance_for != '') {
                $query->join('home_quote_request', function ($join) use ($filters) {
                    $join->on('home_quote_request.uuid', 'personal_quotes.uuid')
                        ->where('home_quote_request.iam_possesion_type_id', $filters->insurance_for);
                });
            }
        }

        if ($lob === quoteTypeCode::Travel) {
            $isTravelQuote = false;
            if ((! empty($filters->insurance_type) && $filters->insurance_type != '') ||
                (! empty($filters->travel_coverage) && $filters->travel_coverage != '')
            ) {
                $isTravelQuote = true;
                $query->join('travel_quote_request', 'travel_quote_request.uuid', 'personal_quotes.uuid');
            }
            if (! empty($filters->insurance_type) && $filters->insurance_type != '') {
                $query->where('travel_quote_request.direction_code', $filters->insurance_type);
            }

            if (! empty($filters->travel_coverage) && $filters->travel_coverage != '') {
                $query->where('travel_quote_request.coverage_code', $filters->travel_coverage);
            }

            if (isset($filters->isEmbeddedProducts) && $filters->isEmbeddedProducts == 'false') {
                $table = $isTravelQuote ? 'travel_quote_request.source' : 'source';
                $query->where($table, '!=', EmbeddedProductEnum::SRC_CAR_EMBEDDED_PRODUCT);
            }

            if (isset($filters->sic_advisor_requested) && $filters->sic_advisor_requested != 'All') {

                $query->join('travel_quote_request', function ($join) use ($filters) {
                    $join->on('travel_quote_request.uuid', 'personal_quotes.uuid')
                        ->where('travel_quote_request.sic_advisor_requested', $filters->sic_advisor_requested);
                });
            }
        }

        if ($lob === quoteTypeCode::Life) {
            if (! empty($filters->insurance_type) && $filters->insurance_type != '') {
                $query->join('life_quote_request', 'life_quote_request.uuid', 'personal_quotes.uuid');
                $query->where('life_quote_request.tenure_of_insurance_id', $filters->insurance_type);
            }
        }

        if ($lob === quoteTypeCode::CORPLINE) {
            $query->join('business_quote_request', 'business_quote_request.uuid', 'personal_quotes.uuid');
            if (! empty($filters->insurance_type) && $filters->insurance_type != '') {
                $query->where('business_quote_request.business_type_of_insurance_id', $filters->insurance_type);
            } else {
                $query->where('business_quote_request.business_type_of_insurance_id', '!=', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            }
        }

        if ($lob === quoteTypeCode::GroupMedical) {
            $query->join('business_quote_request', 'business_quote_request.uuid', 'personal_quotes.uuid');
            $query->where('business_quote_request.business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
        }

        return $query;
    }
}
