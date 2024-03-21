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
use App\Models\PersonalQuote;
use App\Enums\quoteTypeCode;
use App\Enums\PermissionsEnum;
use Illuminate\Support\Facades\Auth;
use App\Repositories\QuoteTypeRepository;
use App\Enums\TravelQuoteEnum;
use App\Enums\quoteBusinessTypeCode;

class AdvisorDistributionReportService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function getReportData($request)
    {
        $lob = $request->lob ?? quoteTypeCode::Car;
        $lob = $lob === quoteTypeCode::GroupMedical ? quoteTypeCode::Business : $lob;
        $lobId = quoteTypeRepository::where('code', $lob)->first();

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

        if(in_array($lob, [quoteTypeCode::Car, quoteTypeCode::Bike])) {
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

        if (auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
            $query = $query->where('users.id', auth()->user()->id);
        } else {
            if (! auth()->user()->hasRole(RolesEnum::LeadPool)) {
                $userIds = $this->walkTree(auth()->user()->id);
                $query = $query->whereIn('personal_quotes.advisor_id', $userIds);
            }
        }

        $query = $this->applyFilters($query, $request->all());

        return $query->paginate(15)
            ->withQueryString();
    }

    public function getFiltersByLob()
    {
        $isAdvisor = Auth::user()->isAdvisor() &&
            !Auth::user()->isManagerOrDeputy() &&
            !Auth::user()->isLeadPool() &&
            !Auth::user()->isAdmin() &&
            !Auth::user()->isEngineer() &&
            !Auth::user()->isSeniorManagement();

        return [
            'advisors' => [
                'can_view' => !$isAdvisor,
            ],
            'teams' => [
                'can_view' => !$isAdvisor,
                'lobs' => [
                    quoteTypeCode::Car,
                    quoteTypeCode::Health,
                    quoteTypeCode::Travel,
                    quoteTypeCode::Life,
                    quoteTypeCode::Home,
                    quoteTypeCode::Pet,
                    quoteTypeCode::Cycle,
                    quoteTypeCode::Yacht,
                    quoteTypeCode::Business,
                    quoteTypeCode::GroupMedical,
                ],
            ],
            'sub_teams' => [
                'can_view' => !$isAdvisor,
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
            'insurance_type' => [
                'lobs' => [
                    quoteTypeCode::Travel,
                    quoteTypeCode::Life,
                    quoteTypeCode::Business,
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
            quoteTypeCode::Business => PermissionsEnum::BUSINESS_DISTRIBUTION_REPORT,
            quoteTypeCode::GroupMedical => PermissionsEnum::GROUPMEDICAL_DISTRIBUTION_REPORT,
        ];

        $lobs = array_filter($lobs, function ($permission) {
            return Auth::user()->can($permission);
        });

        $lobs = QuoteTypeRepository::GetList(array_keys($lobs))->pluck('code', 'text')->toArray();
        $lobs = array_merge(['Group Medical Insurance' => quoteTypeCode::GroupMedical], $lobs);

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
            ->where('is_active', 1)->where('is_applicable_for_rules', 0)
            ->whereNotNull('name')
            ->orderBy('name')
            ->get()
            ->keyBy('name')
            ->map(fn ($users) => $users->name)
            ->toArray();

        $lobs = $this->getLobByPermissions();
        $dropdownSourceService = new DropdownSourceService();

        $insuranceFor = [
            quoteTypeCode::Health => $dropdownSourceService->getDropdownSource('cover_for_id'),
            quoteTypeCode::Home => $dropdownSourceService->getDropdownSource('iam_possesion_type_id'),
        ];

        $travelCoverage = [
            quoteTypeCode::Travel => [
                TravelQuoteEnum::TRAVEL_UAE_INBOUND => [
                    ["value" => TravelQuoteEnum::COVERAGE_CODE_SINGLE_TRIP, 'label' => 'Single Trip'],
                    ["value" => TravelQuoteEnum::COVERAGE_CODE_MULTI_TRIP, 'label' => 'Multi Trip'],
                ],
                TravelQuoteEnum::TRAVEL_UAE_OUTBOUND => [
                    ["value" => TravelQuoteEnum::COVERAGE_CODE_SINGLE_TRIP, 'label' => 'Single Trip'],
                    ["value" => TravelQuoteEnum::COVERAGE_CODE_ANNUAL_TRIP, 'label' => 'Annual Trip'],
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
                ["value" => TravelQuoteEnum::TRAVEL_UAE_INBOUND, 'label' => 'To the UAE (Inbound)'],
                ["value" => TravelQuoteEnum::TRAVEL_UAE_OUTBOUND, 'label' => 'Outside UAE (OutBound)'],
            ],
            quoteTypeCode::Life => $lifeInsuranceType,
            quoteTypeCode::Business => $businessInsuranceType,
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

        return [
            'lob' => quoteTypeCode::Car,
            'advisorAssignedDates' => $advisorAssignedDates,
            'isCommercial' => 'All',
        ];
    }

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $lob = $filters->lob ?? '';

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        $startDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat) :
                ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) : Carbon::parse(now()->subDays($maxDays))->startOfDay()->format($dateFormat));

        $endDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[1])->endOfDay()->format($dateFormat) : Carbon::parse(now())->endOfDay()->format($dateFormat);

        $query->whereBetween('personal_quote_details.advisor_assigned_date', [$startDate, $endDate]);

        if (isset($filters->tiers) && count($filters->tiers) > 0) {
            $query->whereIn('personal_quotes.tier_id', $filters->tiers);
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

        if (isset($filters->leadSources) && count($filters->leadSources) > 0) {
            $query->whereIn('personal_quotes.source', $filters->leadSources);
        }

        if($lob === quoteTypeCode::Car) {

            if (isset($filters->isCommercial) && $filters->isCommercial != 'All') {
                $filters->isCommercial = $filters->isCommercial == 'true' ? true : false;
                $query->join('car_quote_request', 'car_quote_request.uuid', 'personal_quotes.uuid');
                $query->leftJoin('car_model', function($join) use ($filters) {
                    $join->on('car_model.id', 'car_quote_request.car_model_id')
                    ->where('car_model.is_commercial', $filters->isCommercial);
                });
            }
        }

        if($lob === quoteTypeCode::Health) {
            if(!empty($filters->insurance_for) && $filters->insurance_for != '') {
                $query->join('health_quote_request', function($join) use ($filters) {
                    $join->on('health_quote_request.uuid', 'personal_quotes.uuid')
                    ->where('health_quote_request.cover_for_id', $filters->insurance_for);
                });
            }
        }

        if($lob === quoteTypeCode::Home) {
            if(!empty($filters->insurance_for) && $filters->insurance_for != '') {
                $query->join('home_quote_request', function($join) use ($filters) {
                    $join->on('home_quote_request.uuid', 'personal_quotes.uuid')
                    ->where('home_quote_request.iam_possesion_type_id', $filters->insurance_for);
                });
            }
        }


        if($lob === quoteTypeCode::Travel) {
            if((!empty($filters->insurance_type) && $filters->insurance_type != '') ||
                (!empty($filters->travel_coverage) && $filters->travel_coverage != '')) {
                $query->join('travel_quote_request', 'travel_quote_request.uuid', 'personal_quotes.uuid');
            }
            if(!empty($filters->insurance_type) && $filters->insurance_type != '') {
                $query->where('travel_quote_request.direction_code', $filters->insurance_type);
            }

            if(!empty($filters->travel_coverage) && $filters->travel_coverage != '') {
                $query->where('travel_quote_request.coverage_code', $filters->travel_coverage);
            }
        }

        if($lob === quoteTypeCode::Life) {
            if(!empty($filters->insurance_type) && $filters->insurance_type != '') {
                $query->join('life_quote_request', 'life_quote_request.uuid', 'personal_quotes.uuid');
                $query->where('life_quote_request.tenure_of_insurance_id', $filters->insurance_type);
            }
        }

        if($lob === quoteTypeCode::Business) {
            if(!empty($filters->insurance_type) && $filters->insurance_type != '') {
                $query->join('business_quote_request', 'business_quote_request.uuid', 'personal_quotes.uuid');
                $query->where('business_quote_request.business_type_of_insurance_id', $filters->insurance_type);
            }
        }

        if($lob === quoteTypeCode::GroupMedical) {
            $query->join('business_quote_request', 'business_quote_request.uuid', 'personal_quotes.uuid');
            $query->where('business_quote_request.business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
        }

        return $query;
    }
}
