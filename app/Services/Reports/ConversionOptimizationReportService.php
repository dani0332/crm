<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CacheKeyEnum;
use App\Enums\CarRegistrationType;
use App\Enums\ConversionOptimizationCapPercentageEnum;
use App\Enums\EmbeddedProductEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TravelQuoteEnum;
use App\Http\Traits\VehicleTypeTrait;
use App\Models\CarQuote;
use App\Models\Department;
use App\Models\LeadSource;
use App\Models\PersonalQuote;
use App\Models\QuoteBatches;
use App\Models\Team;
use App\Models\Tier;
use App\Models\User;
use App\Models\UserManager;
use App\Repositories\QuoteTypeRepository;
use App\Services\ApplicationStorageService;
use App\Services\BaseService;
use App\Services\Cache\CacheManager;
use App\Services\DropdownSourceService;
use App\Services\Logger\LoggerService;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversionOptimizationReportService extends BaseService
{
    use GetUserTreeTrait;
    use Reportable;
    use TeamHierarchyTrait;
    use VehicleTypeTrait;

    public const NO_DEFAULT_FILTERS_QUERY_PARAM = 'noDefaultFilters';

    public function getReportData($request)
    {
        $builder = $this->getReportQueryBuilder($request);
        if ($builder === null) {
            return [];
        }

        LoggerService::sql(self::class.' - Conversion Optimization Report (base query)', $builder);

        $baseReportData = $this->mapAdvisorConversionQueryResults(collect($builder->get()));

        if ($baseReportData->isEmpty()) {
            return [];
        }

        return $this->applyPostQueryCalculations($baseReportData, (array) $request->all())->values()->all();
    }

    public function mergeDefaultsIntoRequest(Request $request, array $defaultFilters): Request
    {
        $noDefaultFiltersParam = self::NO_DEFAULT_FILTERS_QUERY_PARAM;
        $skipDefaultFilters = $request->boolean($noDefaultFiltersParam);

        $clientSpecifiedAnyDefaultFilterKey = false;

        foreach (array_keys($defaultFilters) as $key) {
            if ($request->query->has($key) || $request->request->has($key)) {
                $clientSpecifiedAnyDefaultFilterKey = true;

                break;
            }
        }

        if ($skipDefaultFilters || $clientSpecifiedAnyDefaultFilterKey) {
            $mergedQuery = $request->query->all();
            $mergedRequest = $request->request->all();
        } else {
            $mergedQuery = array_merge($defaultFilters, $request->query->all());
            $mergedRequest = array_merge($defaultFilters, $request->request->all());
        }

        unset($mergedQuery[$noDefaultFiltersParam], $mergedRequest[$noDefaultFiltersParam]);

        LoggerService::info('mergeDefaultsIntoRequest', [
            '$defaultFilters' => $defaultFilters,
            'query' => $request->query->all(),
            'request' => $request->request->all(),
            'clientSpecifiedAnyDefaultFilterKey' => $clientSpecifiedAnyDefaultFilterKey,
            'skipDefaultFilters' => $skipDefaultFilters,
        ]);

        if ($this->conversionOptimizationClientChoseTeamsWithoutSubTeams($request)) {
            unset($mergedQuery['sub_teams'], $mergedRequest['sub_teams']);
        }

        return $request->duplicate($mergedQuery, $mergedRequest);
    }

    /**
     * When the UI sends teams but omits sub_teams (empty sub_teams are stripped client-side),
     * defaults must not inject organic VALUE/VOLUME sub-teams — those only match the default team.
     */
    private function conversionOptimizationClientChoseTeamsWithoutSubTeams(Request $request): bool
    {
        $teamsKeyProvided = $request->query->has('teams') || $request->request->has('teams');
        $subTeamsKeyProvided = $request->query->has('sub_teams') || $request->request->has('sub_teams');

        return $teamsKeyProvided && ! $subTeamsKeyProvided;
    }

    public function getReportQueryBuilder($request): ?Builder
    {
        $lob = $request->lob ?? '';

        if (empty($lob)) {
            return null;
        }

        $filters = (object) [
            'advisorAssignedDates' => $request->advisorAssignedDates,
            'ecommerceFilter' => $request->is_ecommerce,
            'excludeCreatedLeadsFilter' => $request->excludeCreatedLeadsFilter,
            'batchNumberFilter' => $request->batches,
            'tiersFilter' => $request->tiers,
            'leadSourceFilter' => $request->leadSources,
            'teamsFilter' => $request->teams,
            'advisorsFilter' => $request->advisors,
            'quoteBatchId' => $request->quote_batch_id,
            'isCommercial' => $request->isCommercial,
            'isEmbeddedProducts' => $request->isEmbeddedProducts,
            'page' => $request->page,
            'lob' => $lob,
            'subteams' => $request->sub_teams,
            'vehicle_type' => $request->vehicle_type,
            'insurance_type' => $request->insurance_type,
            'insurance_for' => $request->insurance_for,
            'travel_coverage' => $request->travel_coverage,
            'segment_filter' => $request->segment_filter,
            'registration_type' => $request->registration_type,
            'vehicle_use' => $request->vehicle_use,
            'departmentIds' => $this->resolveDepartmentIdsForReport($request->department),
        ];

        if ($lob === quoteTypeCode::Car) {
            return $this->applyFiltersForCar($this->buildCarAdvisorAggregateQuery(), $filters);
        }

        return $this->applyFiltersForPersonal($this->buildPersonalAdvisorAggregateQuery($lob), $filters, $lob);
    }

    public function mapAdvisorConversionQueryResults(Collection $rows): Collection
    {
        return $rows->map(function ($row) {
            $netDenominator = $row->total_leads - $row->bad_leads;
            $grossDenominator = $row->total_leads;

            $row->net_conversion = (float) $netDenominator > 0
                ? round(($row->sale_leads / $netDenominator) * 100, 2)
                : 0;
            $row->gross_conversion = (float) $grossDenominator > 0
                ? round(($row->sale_leads / $grossDenominator) * 100, 2)
                : 0;

            return $row;
        });
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
            quoteTypeCode::SAVINGS => ! Auth::user()->hasRole(RolesEnum::SavingsAdvisor),
            quoteTypeCode::CYBER => ! Auth::user()->hasRole(RolesEnum::CyberAdvisor),
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
            'is_ecommerce' => [
                'lobs' => [
                    quoteTypeCode::Car,
                    quoteTypeCode::Bike,
                    quoteTypeCode::Health,
                    quoteTypeCode::Travel,
                ],
            ],
            'vehicle_type' => [
                'lobs' => [
                    quoteTypeCode::Car,
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
                    quoteTypeCode::Health,
                    quoteTypeCode::Travel,
                    quoteTypeCode::Life,
                ],
            ],
        ];
    }

    public function getLobByPermissions()
    {
        $lobs = [
            quoteTypeCode::Car => PermissionsEnum::ADVISOR_CONVERSION_REPORT_VIEW,
            quoteTypeCode::Bike => PermissionsEnum::BIKE_CONVERSION_REPORT,
            quoteTypeCode::Health => PermissionsEnum::HEALTH_CONVERSION_REPORT,
            quoteTypeCode::Travel => PermissionsEnum::TRAVEL_CONVERSION_REPORT,
            quoteTypeCode::Pet => PermissionsEnum::PET_CONVERSION_REPORT,
            quoteTypeCode::Cycle => PermissionsEnum::CYCLE_CONVERSION_REPORT,
            quoteTypeCode::Yacht => PermissionsEnum::YACHT_CONVERSION_REPORT,
            quoteTypeCode::Life => PermissionsEnum::LIFE_CONVERSION_REPORT,
            quoteTypeCode::Home => PermissionsEnum::HOME_CONVERSION_REPORT,
            quoteTypeCode::SAVINGS => PermissionsEnum::SAVINGS_CONVERSION_REPORT,
            quoteTypeCode::CYBER => PermissionsEnum::CYBER_CONVERSION_REPORT,
        ];

        $lobs = array_filter($lobs, function ($permission, $lob) {
            return Auth::user()->can($permission) || Auth::user()->can(PermissionsEnum::VIEW_ALL_REPORTS) && userHasProduct($lob);
        }, ARRAY_FILTER_USE_BOTH);

        $lobs = QuoteTypeRepository::GetList()
            ->filter(function ($lob) use ($lobs) {
                return array_key_exists($lob->code, $lobs);
            })
            ->pluck('code', 'text')
            ->toArray();

        if (Auth::user()->can(PermissionsEnum::CORPLINE_CONVERSION_REPORT) || (userHasProduct(quoteTypeCode::CORPLINE) && Auth::user()->can(PermissionsEnum::VIEW_ALL_REPORTS))) {
            $lobs = array_merge(['CorpLine Insurance' => quoteTypeCode::CORPLINE], $lobs);
        }

        if (Auth::user()->can(PermissionsEnum::GROUPMEDICAL_CONVERSION_REPORT) || (userHasProduct(quoteTypeCode::GroupMedical) && Auth::user()->can(PermissionsEnum::VIEW_ALL_REPORTS))) {
            $lobs = array_merge(['Group Medical Insurance' => quoteTypeCode::GroupMedical], $lobs);
        }

        return $lobs;
    }

    /**
     * Teams shown in the Conversion Optimization team multi-select.
     * Same team id resolution and query as {@see ManagementReport::getFilterOptions()}.
     *
     * @return list<array{value: int, label: string}>
     */
    private function getConversionOptimizationTeamFilterOptions(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        if ($user->isDepartmentManager()) {
            $user->load('departments.teams');
            $teamIds = $user->departments->reduce(function ($carry, $department) {
                return $carry->merge(
                    $department->teams->pluck('team_id')
                );
            }, collect());

            $teamIds = $teamIds->all();
        } else {
            $teamIds = $this->getUserTeams($user->id)->pluck('id')->all();
        }

        if ($teamIds === []) {
            return [];
        }

        return Team::query()
            ->whereIn('id', $teamIds)
            ->where('type', 2)
            ->select('name', 'id')
            ->orderBy('name')
            ->active()
            ->get()
            ->map(fn (Team $team): array => [
                'value' => (int) $team->id,
                'label' => (string) $team->name,
            ])
            ->values()
            ->all();
    }

    public function getFilterOptions()
    {
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $advisors = [];
        $teams = $this->getConversionOptimizationTeamFilterOptions();

        $batches = QuoteBatches::query()
            ->select('name', 'start_date', 'end_date', 'id')
            ->orderBy('id')
            ->get()
            ->keyBy('id')
            ->map(function ($batch) {
                $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
                $startDate = Carbon::parse($batch->start_date)->format($dateFormat);
                $endDate = Carbon::parse($batch->end_date)->format($dateFormat);

                return $batch->name.'-('.$startDate.' to '.$endDate.')';
            })
            ->toArray();

        $tiers = Tier::query()
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($tier) => $tier->name)
            ->toArray();

        $leadSources = LeadSource::query()
            ->select('name')
            ->where('is_active', 1)
            ->whereNotNull('name')
            ->orderBy('name')
            ->get()
            ->keyBy('name')
            ->map(fn ($leadSource) => $leadSource->name)
            ->toArray();

        $departments = Department::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->whereIn('id', Auth::user()->departments->pluck('id')->toArray())
            ->orderBy('name')
            ->get()
            ->map(fn ($department) => [
                'value' => $department->id,
                'label' => $department->name,
            ])
            ->values()
            ->all();

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

        $lifeInsuranceType = $dropdownSourceService->getDropdownSource('tenure_of_insurance_id')
            ->map(function ($type) {
                return ['value' => $type['id'], 'label' => $type['text']];
            })
            ->toArray();

        $businessInsuranceType = $dropdownSourceService->getDropdownSource('business_type_of_insurance_id')
            ->filter(function ($type) {
                return $type['text'] != quoteBusinessTypeCode::groupMedical;
            })
            ->map(function ($type) {
                return ['value' => $type['id'], 'label' => $type['text']];
            })
            ->values()
            ->toArray();

        $insuranceType = [
            quoteTypeCode::Travel => [
                ['value' => TravelQuoteEnum::TRAVEL_UAE_INBOUND, 'label' => 'To the UAE (Inbound)'],
                ['value' => TravelQuoteEnum::TRAVEL_UAE_OUTBOUND, 'label' => 'Outside UAE (OutBound)'],
            ],
            quoteTypeCode::Life => $lifeInsuranceType,
            quoteTypeCode::CORPLINE => $businessInsuranceType,
        ];

        $vehicleCategories = $this->getVehicleTypes()
            ->pluck('text')
            ->map(function ($category) {
                return ['value' => ucwords($category), 'label' => ucwords(strtolower($category))];
            })
            ->toArray();

        return [
            'lob' => $lobs,
            'maxDays' => $maxDays,
            'batches' => $batches,
            'tiers' => $tiers,
            'leadSources' => $leadSources,
            'departments' => $departments,
            'advisors' => $advisors,
            'teams' => $teams,
            'insurance_for' => $insuranceFor,
            'travel_coverage' => $travelCoverage,
            'insurance_type' => $insuranceType,
            'vehicle_type' => [
                quoteTypeCode::Car => $vehicleCategories,
            ],
            'capPercentages' => ConversionOptimizationCapPercentageEnum::withLabels(),
        ];
    }

    public function getDefaultFilters()
    {
        $dateFormat = config('constants.DATE_FORMAT_ONLY');
        $defaultFilters = [
            'lob' => quoteTypeCode::Car,
            'isCommercial' => 'All',
            'isEmbeddedProducts' => false,
            'department' => [],
        ];

        // $teamFilters = $this->getCachedDefaultConversionOptimizationTeamFilters();

        return array_merge($defaultFilters, [
            'advisorAssignedDates' => [
                now()->subWeeks(8)->startOfDay()->format($dateFormat),
                now()->endOfDay()->format($dateFormat),
            ],
            'teams' => [],
            'sub_teams' => [],
            'cap_percentage' => '',
        ]);
    }

    /**
     * Cached organic team id and default sub-team ids for this report (two Team queries).
     * Date-based defaults stay outside the cache so advisor ranges stay current.
     *
     * @return array{organic_team_id: int|null, sub_team_ids: list<string>}
     */
    private function getCachedDefaultConversionOptimizationTeamFilters(): array
    {
        /* keeping it commented, for possibility of needing it in future */
        /* return CacheManager::remember(CacheKeyEnum::CONVERSION_OPTIMIZATION_DEFAULT_TEAM_FILTERS, function (): array {
             $organicTeamId = Team::query()
                 ->where('name', TeamNameEnum::ORGANIC)
                 ->where('is_active', 1)
                 ->value('id');

             $defaultSubTeamIds = Team::query()
                 ->whereIn('name', [TeamNameEnum::VALUE, TeamNameEnum::VOLUME])
                 ->whereIn('parent_team_id', [$organicTeamId])
                 ->where('is_active', 1)
                 ->pluck('id')
                 ->map(fn ($id) => (string) $id)
                 ->values()
                 ->all();

             return [
                 'organic_team_id' => $organicTeamId !== null ? (int) $organicTeamId : null,
                 'sub_team_ids' => $defaultSubTeamIds,
             ];
         });*/
    }

    public function applyFiltersForCar(Builder $query, object $filters): Builder
    {
        [$freshLoad, $startDate, $endDate] = $this->getStartAndEndDate($filters);

        $query->filterByAdvisors($filters->advisorsFilter)
            ->filterByBatches($filters->quoteBatchId)
            ->filterByBatches($filters->batchNumberFilter)
            ->filterByTeams($filters->teamsFilter)
            ->filterBySubTeams($filters->subteams)
            ->filterByTiers($filters->tiersFilter)
            ->filterBySegment()
            ->when($freshLoad || isset($filters->advisorAssignedDates), function ($builder) use ($startDate, $endDate) {
                $builder->whereBetween('car_quote_request_detail.advisor_assigned_date', [$startDate, $endDate]);
            })
            ->when(isset($filters->ecommerceFilter) && $filters->ecommerceFilter !== 'All', function ($builder) use ($filters) {
                $builder->where('car_quote_request.is_ecommerce', $filters->ecommerceFilter === 'Yes');
            })
            ->when(isset($filters->isCommercial) && $filters->isCommercial !== 'All', function ($builder) use ($filters) {
                $isCommercial = $filters->isCommercial === 'true';
                $builder->where('car_model.is_commercial', $isCommercial);
            })
            ->when(! empty($filters->vehicle_type) && $filters->vehicle_type !== 'All', function ($builder) use ($filters) {
                $builder->join('vehicle_type', function ($join) use ($filters) {
                    $join->on('vehicle_type.id', 'car_quote_request.vehicle_type_id')
                        ->where('vehicle_type.category', $filters->vehicle_type);
                });
            })
            ->when(isset($filters->excludeCreatedLeadsFilter) && $filters->excludeCreatedLeadsFilter === 'yes', function ($builder) {
                $builder->whereNotIn('car_quote_request.source', $this->getExcludedSources());
            })
            ->when(isset($filters->leadSourceFilter) && ! empty($filters->leadSourceFilter), function ($builder) use ($filters) {
                $builder->whereIn('car_quote_request.source', $filters->leadSourceFilter);
            }, function ($builder) {
                $builder->whereNotIn('car_quote_request.source', [
                    LeadSourceEnum::RENEWAL_UPLOAD,
                    LeadSourceEnum::INSLY,
                    LeadSourceEnum::REVIVAL,
                    LeadSourceEnum::IMCRM,
                    LeadSourceEnum::REVIVAL_PAID,
                    LeadSourceEnum::REVIVAL_REPLIED,
                    LeadSourceEnum::CROSS_SELL,
                ]);
            })
            ->when(! empty($filters->registration_type) && $filters->registration_type !== 'All', function ($builder) use ($filters) {
                $builder->where('car_quote_request.registration_type', $filters->registration_type);
            })
            ->when(
                ! empty($filters->vehicle_use)
                    && $filters->vehicle_use !== 'All'
                    && $filters->registration_type === CarRegistrationType::COMPANY,
                function ($builder) use ($filters) {
                    $builder->where('car_quote_request.vehicle_use', $filters->vehicle_use);
                }
            )
            ->when(! empty($filters->departmentIds), function ($builder) use ($filters) {
                $builder->whereIn('users.department_id', $filters->departmentIds);
            });

        return $query;
    }

    public function applyFiltersForPersonal(Builder $query, object $filters, string $lob): Builder
    {
        [$freshLoad, $startDate, $endDate] = $this->getStartAndEndDate($filters);

        $query->filterByAdvisors($filters->advisorsFilter)
            ->filterByBatches($filters->quoteBatchId)
            ->filterByBatches($filters->batchNumberFilter)
            ->filterByTeams($filters->teamsFilter)
            ->filterBySubTeams($filters->subteams)
            ->when($lob === quoteTypeCode::Travel, function ($builder) use ($filters) {
                $builder->filterBySegment($filters->segment_filter, QuoteTypeId::Travel);
            })
            ->when($lob === quoteTypeCode::Health, function ($builder) use ($filters) {
                $builder->filterBySegment($filters->segment_filter, QuoteTypeId::Health);
            })
            ->when($lob === quoteTypeCode::Car, function ($builder) use ($filters) {
                $builder->filterBySegment($filters->segment_filter, QuoteTypeId::Car);
            })
            ->when($lob === quoteTypeCode::Life, function ($builder) use ($filters) {
                $builder->filterBySegment($filters->segment_filter, QuoteTypeId::Life);
            })
            ->when($freshLoad || isset($filters->advisorAssignedDates), function ($builder) use ($startDate, $endDate) {
                $builder->whereBetween('personal_quote_details.advisor_assigned_date', [$startDate, $endDate]);
            })
            ->when(isset($filters->ecommerceFilter) && $filters->ecommerceFilter !== 'All', function ($builder) use ($filters) {
                $builder->where('personal_quotes.is_ecommerce', $filters->ecommerceFilter === 'Yes');
            })
            ->when(isset($filters->excludeCreatedLeadsFilter) && $filters->excludeCreatedLeadsFilter === 'yes', function ($builder) {
                $builder->whereNotIn('personal_quotes.source', $this->getExcludedSources());
            })
            ->when(isset($filters->leadSourceFilter) && ! empty($filters->leadSourceFilter), function ($builder) use ($filters) {
                $builder->whereIn('personal_quotes.source', $filters->leadSourceFilter);
            }, function ($builder) {
                $builder->whereNotIn('personal_quotes.source', [
                    LeadSourceEnum::RENEWAL_UPLOAD,
                    LeadSourceEnum::INSLY,
                    LeadSourceEnum::REVIVAL,
                    LeadSourceEnum::IMCRM,
                    LeadSourceEnum::REVIVAL_PAID,
                    LeadSourceEnum::REVIVAL_REPLIED,
                    LeadSourceEnum::CROSS_SELL,
                ]);
            })
            ->when($lob === quoteTypeCode::Health, function ($builder) use ($filters) {
                $builder->when(! empty($filters->insurance_for), function ($query) use ($filters) {
                    $query->join('health_quote_request', function ($join) use ($filters) {
                        $join->on('health_quote_request.uuid', 'personal_quotes.uuid')
                            ->where('health_quote_request.cover_for_id', $filters->insurance_for);
                    });
                });
            })
            ->when($lob === quoteTypeCode::Home, function ($builder) use ($filters) {
                $builder->when(! empty($filters->insurance_for), function ($query) use ($filters) {
                    $query->join('home_quote_request', function ($join) use ($filters) {
                        $join->on('home_quote_request.uuid', 'personal_quotes.uuid')
                            ->where('home_quote_request.iam_possesion_type_id', $filters->insurance_for);
                    });
                });
            })
            ->when($lob === quoteTypeCode::Life, function ($builder) use ($filters) {
                $builder->when(! empty($filters->insurance_type), function ($query) use ($filters) {
                    $query->join('life_quote_request', 'life_quote_request.uuid', 'personal_quotes.uuid')
                        ->where('life_quote_request.tenure_of_insurance_id', $filters->insurance_type);
                });
            })
            ->when($lob === quoteTypeCode::CORPLINE, function ($builder) use ($filters) {
                $builder->join('business_quote_request', 'business_quote_request.uuid', 'personal_quotes.uuid')
                    ->when(! empty($filters->insurance_type), function ($query) use ($filters) {
                        $query->where('business_quote_request.business_type_of_insurance_id', $filters->insurance_type);
                    }, function ($query) {
                        $query->where(
                            'business_quote_request.business_type_of_insurance_id',
                            '!=',
                            quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)
                        );
                    });
            })
            ->when($lob === quoteTypeCode::GroupMedical, function ($builder) {
                $builder->join('business_quote_request', 'business_quote_request.uuid', 'personal_quotes.uuid')
                    ->where(
                        'business_quote_request.business_type_of_insurance_id',
                        quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)
                    );
            });

        $this->applyTravelFilters($query, $filters, $lob);

        $query->when(! empty($filters->departmentIds), function ($builder) use ($filters) {
            $builder->whereIn('users.department_id', $filters->departmentIds);
        });

        return $query;
    }

    public function applyPostQueryCalculations(Collection $reportRows, array $filters): Collection
    {
        $advisorMetadata = $this->getAdvisorMetadata(
            $reportRows->pluck('advisorId')
                ->filter()
                ->map(fn ($advisorId) => (int) $advisorId)
                ->unique()
                ->values()
                ->all(),
            $filters['lob'] ?? null
        );

        $normalizedRows = $reportRows->map(function ($row) use ($advisorMetadata) {
            $metadata = $advisorMetadata->get((int) $row->advisorId);

            $row->conversion = round((float) ($row->net_conversion ?? 0), 2);
            $row->team_id = $metadata?->team_id;
            $row->team_name = $metadata?->team_name;
            $row->sub_team_id = $metadata?->sub_team_id;
            $row->sub_team_name = $metadata?->sub_team_name;
            $row->original_max_capacity = isset($metadata?->max_capacity) ? (int) $metadata->max_capacity : null;
            $row->current_cap = $row->original_max_capacity;
            $row->ranking = null;
            $row->team_average = null;
            $row->expected_sales = null;
            $row->required_sales = null;
            $row->new_conversion = null;
            $row->suggested_cap = null;
            $row->total_average = null;

            return $row;
        });

        $capPercentage = $this->resolveCapPercentage($filters['cap_percentage'] ?? null);

        /** Commented for future use for multiple groups */
        // Former multi-cohort grouping: ranked separately per resolveCohortKey() (e.g. by sub-team when team filters empty).
        // Product requirement: one ranked list for the entire filtered dataset (filters already narrow rows).
        // $cohorts = $normalizedRows->groupBy(fn ($row) => $this->resolveCohortKey($row, $filters));
        //
        // foreach ($cohorts as $cohortRows) {
        $rankedRows = $normalizedRows
            ->sort(function ($leftRow, $rightRow) {
                $conversionComparison = $rightRow->conversion <=> $leftRow->conversion;

                if ($conversionComparison !== 0) {
                    return $conversionComparison;
                }

                $advisorNameComparison = strcmp((string) ($leftRow->advisor_name ?? ''), (string) ($rightRow->advisor_name ?? ''));

                if ($advisorNameComparison !== 0) {
                    return $advisorNameComparison;
                }

                return ((int) ($leftRow->advisorId ?? 0)) <=> ((int) ($rightRow->advisorId ?? 0));
            })
            ->values();

        $teamAverage = round((float) $rankedRows->avg(fn ($row) => (float) $row->conversion), 2);

        foreach ($rankedRows as $index => $row) {
            $row->ranking = $index + 1;
            $row->team_average = $teamAverage;

            /** Since above "multi-cohort grouping" is disabled both team and total average will be same. */
            $row->total_average = $teamAverage;

            if ((float) $row->conversion < $teamAverage && (float) $row->total_leads > 0) {
                $row->expected_sales = $this->roundWithPointOneFractionBias(
                    ((float) $row->total_leads * $teamAverage) / 100
                );
                $row->required_sales = $this->roundWithPointOneFractionBias(
                    (float) $row->expected_sales - (float) $row->sale_leads
                );
                $row->new_conversion = $this->roundWithPointOneFractionBias(
                    ($row->expected_sales / (float) $row->total_leads) * 100
                );
            }
        }

        $this->applyCapLimitCalculations($rankedRows, $capPercentage);
        // } // end of foreach $cohorts

        // Below gets enable when above "multi-cohort grouping" is enabled

        /* Calculates total average across groups when multiple groups logic is enabled */

        /*if ($normalizedRows->isNotEmpty()) {
            $datasetAverageConversion = round(
                (float) $normalizedRows->avg(fn ($row) => (float) $row->conversion),
                2
            );

            foreach ($normalizedRows as $row) {
                $row->total_average = $datasetAverageConversion;
            }
        }*/

        return $rankedRows;
    }

    protected function getAdvisorMetadata(array $advisorIds, ?string $lob): Collection
    {
        if ($advisorIds === []) {
            return collect();
        }

        $leadAllocationQuoteTypeId = $this->resolveLeadAllocationQuoteTypeId($lob);
        $teamSubQuery = DB::table('user_team')
            ->select('user_id', DB::raw('MIN(team_id) as team_id'))
            ->groupBy('user_id');

        return User::query()
            ->select(
                'users.id',
                'users.sub_team_id',
                'sub_teams.name as sub_team_name',
                'advisor_team.team_id',
                'teams.name as team_name',
                'lead_allocation.max_capacity'
            )
            ->leftJoinSub($teamSubQuery, 'advisor_team', function ($join) {
                $join->on('advisor_team.user_id', '=', 'users.id');
            })
            ->leftJoin('teams', 'teams.id', '=', 'advisor_team.team_id')
            ->leftJoin('teams as sub_teams', 'sub_teams.id', '=', 'users.sub_team_id')
            ->leftJoin('lead_allocation', function ($join) use ($leadAllocationQuoteTypeId) {
                $join->on('lead_allocation.user_id', '=', 'users.id');

                if ($leadAllocationQuoteTypeId !== null) {
                    $join->where('lead_allocation.quote_type_id', '=', $leadAllocationQuoteTypeId);
                }
            })
            ->whereIn('users.id', $advisorIds)
            ->get()
            ->keyBy('id');
    }

    private function buildCarAdvisorAggregateQuery(): Builder
    {
        $query = CarQuote::query()
            ->select(
                'users.id as advisorId',
                'users.name as advisor_name',
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->where('users.is_active', true)
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.email');

        $this->restrictAdvisorsToViewerTree($query, 'car_quote_request.advisor_id', quoteTypeCode::Car);
        $this->addSelect($query, 'car_quote_request', quoteTypeCode::Car);

        return $query;
    }

    private function buildPersonalAdvisorAggregateQuery(string $lob): Builder
    {
        $normalizedLob = in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE], true)
            ? quoteTypeCode::Business
            : $lob;
        $lobId = QuoteTypeRepository::where('code', $normalizedLob)->value('id');

        $query = PersonalQuote::query()
            ->select(
                'users.id as advisorId',
                'users.name as advisor_name',
            )
            ->join('users', 'users.id', 'personal_quotes.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'personal_quotes.quote_batch_id')
            ->join('personal_quote_details', 'personal_quote_details.personal_quote_id', 'personal_quotes.id')
            ->where('users.is_active', true)
            ->when($lobId !== null, function ($builder) use ($lobId) {
                $builder->where('personal_quotes.quote_type_id', $lobId);
            }, function ($builder) {
                $builder->whereRaw('1 = 0');
            })
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.email');

        $this->restrictAdvisorsToViewerTree($query, 'personal_quotes.advisor_id', $lob);
        $this->addSelect($query, 'personal_quotes', $lob);

        return $query;
    }

    private function restrictAdvisorsToViewerTree(Builder $query, string $advisorColumn, string $lob): void
    {
        if (
            auth()->user()->hasAnyRole([
                RolesEnum::LeadPool,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ])
            || auth()->user()->can(PermissionsEnum::VIEW_ALL_REPORTS)
        ) {
            return;
        }

        $userIds = $this->walkTree(auth()->user()->id, $lob);

        if (auth()->user()->isManagerORDeputy()) {
            $userIds = UserManager::where('manager_id', auth()->user()->id)
                ->get()
                ->filter(function ($user) use ($userIds) {
                    return in_array($user->user_id, $userIds);
                })
                ->pluck('user_id')
                ->toArray();

            if ($lob === quoteTypeCode::Health) {
                $userIds = $this->getUsers($userIds);
            }
        }

        $query->whereIn($advisorColumn, $userIds);
    }

    private function getUsers(array $userIds): array
    {
        return User::whereIn('id', $userIds)
            ->where('department_id', auth()->user()->department_id)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Normalize multi-select department filter to allowed, active department ids (user_departments).
     *
     * @return list<int>
     */
    private function resolveDepartmentIdsForReport(mixed $raw): array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return [];
        }

        $candidates = is_array($raw) ? $raw : [$raw];

        $allowedIds = Department::query()
            ->where('is_active', true)
            ->whereIn('id', Auth::user()->departments->pluck('id')->toArray())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $normalized = [];

        foreach ($candidates as $item) {
            if ($item === null || $item === '') {
                continue;
            }

            $id = (int) $item;

            if ($id > 0 && in_array($id, $allowedIds, true)) {
                $normalized[] = $id;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function getAdvisorConversionQuoteStatusDate(): Carbon
    {
        return cache()->remember('advisor_conversion_quote_status_date', now()->addHour(), function () {
            return Carbon::parse(getAppStorageValueByKey(ApplicationStorageEnums::ADVISOR_CONVERSION_QUOTE_STATUS_DATE));
        });
    }

    private function getApprovedStatuses(): array
    {
        return [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
        ];
    }

    private function getSaleStatuses(): array
    {
        return [
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::POLICY_BOOKING_FAILED,
        ];
    }

    private function getBindings(string $table): array
    {
        $excludedSources = implode(',', array_map(fn ($source) => "'$source'", $this->getExcludedSources()));

        return [
            ':table' => $table,
            ':excludedSources' => $excludedSources,
            ':quoteStatusDate' => $this->getAdvisorConversionQuoteStatusDate(),
            ':saleStatuses' => implode(',', $this->getSaleStatuses()),
            ':approvedStatuses' => implode(',', $this->getApprovedStatuses()),
            ':badLeadsStatuses' => implode(',', $this->getBadLeadStatuses()),
            ':paidStatuses' => implode(',', $this->getPaidStatuses()),
        ];
    }

    private function addSelect(Builder $query, string $table, string $lob): void
    {
        $bindings = $this->getBindings($table);

        $buildCaseSum = function (string $condition, string $alias) use ($bindings) {
            return DB::raw(strtr("SUM(CASE WHEN {$condition} THEN 1 ELSE 0 END) as {$alias}", $bindings));
        };

        $getSaleLeadsQuery = function (string $sourceCondition, string $alias) use ($bindings) {
            $condition = "(
                ((:table.payment_status_id IN (:paidStatuses) OR :table.quote_status_id IN (:approvedStatuses)) AND :table.transaction_approved_at IS NULL) OR
                (:table.quote_status_id IN (:approvedStatuses) AND :table.transaction_approved_at < \":quoteStatusDate\") OR
                (:table.quote_status_id IN (:saleStatuses) AND :table.transaction_approved_at >= \":quoteStatusDate\")
            ) AND :table.source {$sourceCondition} (:excludedSources)";

            return DB::raw(strtr("SUM(CASE WHEN {$condition} THEN 1 ELSE 0 END) as {$alias}", $bindings));
        };

        $query->addSelect(
            $buildCaseSum(':table.source NOT IN (:excludedSources)', 'total_leads'),
            $buildCaseSum(':table.quote_status_id IN (:badLeadsStatuses) AND :table.source NOT IN (:excludedSources)', 'bad_leads'),
            $getSaleLeadsQuery('NOT IN', 'sale_leads'),
        );
    }

    private function applyTravelFilters(Builder $query, object $filters, string $lob): void
    {
        $query->when($lob === quoteTypeCode::Travel, function ($builder) use ($filters) {
            $isTravelQuote = (! empty($filters->insurance_type) && $filters->insurance_type !== '')
                || (! empty($filters->travel_coverage) && $filters->travel_coverage !== '');

            $builder->when($isTravelQuote, function ($query) {
                $query->join('travel_quote_request', 'travel_quote_request.uuid', 'personal_quotes.uuid');
            })
                ->when(! empty($filters->insurance_type) && $filters->insurance_type !== '', function ($query) use ($filters) {
                    $query->where('travel_quote_request.direction_code', $filters->insurance_type);
                })
                ->when(! empty($filters->travel_coverage) && $filters->travel_coverage !== '', function ($query) use ($filters) {
                    $query->where('travel_quote_request.coverage_code', $filters->travel_coverage);
                })
                ->when(isset($filters->isEmbeddedProducts) && $filters->isEmbeddedProducts === 'false', function ($query) use ($isTravelQuote) {
                    $sourceColumn = $isTravelQuote ? 'travel_quote_request.source' : 'personal_quotes.source';
                    $query->where($sourceColumn, '!=', EmbeddedProductEnum::SRC_CAR_EMBEDDED_PRODUCT);
                });
        });
    }

    private function resolveLeadAllocationQuoteTypeId(?string $lob): ?int
    {
        if (empty($lob)) {
            return null;
        }

        $normalizedLob = in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE], true)
            ? quoteTypeCode::Business
            : $lob;

        return QuoteTypeRepository::where('code', $normalizedLob)->value('id');
    }

    /*
    private function resolveCohortKey(object $row, array $filters): string
    {
        $selectedSubTeams = $this->normalizeSelectedIds($filters['sub_teams'] ?? []);

        if ($selectedSubTeams !== []) {
            return 'selected-sub-teams';
        }

        $selectedTeams = $this->normalizeSelectedIds($filters['teams'] ?? []);

        if ($selectedTeams !== []) {
            return 'selected-teams';
        }

        if (! empty($row->sub_team_id)) {
            return 'sub-team:'.$row->sub_team_id;
        }

        if (! empty($row->team_id)) {
            return 'team:'.$row->team_id;
        }

        return 'dataset';
    }

    private function normalizeSelectedIds(mixed $selectedValues): array
    {
        return collect(is_array($selectedValues) ? $selectedValues : [$selectedValues])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->values()
            ->all();
    }
    */

    private function resolveCapPercentage(mixed $capPercentage): ?int
    {
        if ($capPercentage === null || $capPercentage === '') {
            return null;
        }

        return (int) $capPercentage;
    }

    private function applyCapLimitCalculations(Collection $rankedRows, ?int $capPercentage): void
    {
        if ($capPercentage === null || $capPercentage <= 0 || $rankedRows->isEmpty()) {
            return;
        }

        $cappedAdvisorCount = (int) ceil(($rankedRows->count() * $capPercentage) / 100);

        if ($cappedAdvisorCount <= 0) {
            return;
        }

        $cappedRows = $rankedRows->take(-$cappedAdvisorCount)->values();
        $worstRankedRow = $cappedRows->last();

        foreach ($cappedRows as $row) {
            if ($worstRankedRow !== null && (int) $row->advisorId === (int) $worstRankedRow->advisorId) {
                $row->suggested_cap = 0;

                continue;
            }

            if ($row->original_max_capacity === null || (int) $row->original_max_capacity <= 0) {
                $row->suggested_cap = null;

                continue;
            }

            $row->suggested_cap = $this->roundWithPointOneFractionBias(
                ((int) $row->original_max_capacity * $capPercentage) / 100
            );
        }
    }

    /**
     * Whole number: if the fractional part is ~0.1, floor; otherwise ceil.
     * Used for cap limits, expected/required sales, and new conversion %.
     */
    private function roundWithPointOneFractionBias(float $value): int
    {
        // Stabilise binary float noise (e.g. (55/100)*100 becoming 55.00000000001 and ceil → 56).
        $value = round($value, 8);

        $decimalPart = $value - floor($value);

        if (abs($decimalPart - 0.1) < 0.00001) {
            return (int) floor($value);
        }

        return (int) ceil($value);
    }
}
