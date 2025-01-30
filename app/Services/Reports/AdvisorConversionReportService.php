<?php

namespace App\Services\Reports;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\ReportsLeadTypeEnum;
use App\Enums\RolesEnum;
use App\Enums\TravelQuoteEnum;
use App\Http\Traits\VehicleTypeTrait;
use App\Models\CarQuote;
use App\Models\LeadSource;
use App\Models\PersonalQuote;
use App\Models\QuoteBatches;
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

class AdvisorConversionReportService extends BaseService
{
    use GetUserTreeTrait;
    use Reportable;
    use TeamHierarchyTrait;
    use VehicleTypeTrait;

    public function getReportData($request)
    {
        $lob = $request->lob ?? '';
        if (empty($lob)) {
            return [];
        }

        $filters = [
            'advisorId' => $request->advisorId,
            'leadType' => $request->leadType,
            'advisorAssignedDates' => $request->advisorAssignedDates,
            'createdAtFilter' => $request->createdAtFilter,
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
            'lob' => $request->lob,
            'subteams' => $request->sub_teams,
            'vehicle_type' => $request->vehicle_type,
            'insurance_type' => $request->insurance_type,
            'insurance_for' => $request->insurance_for,
            'travel_coverage' => $request->travel_coverage,
            'segment_filter' => $request->segment_filter,
        ];

        if ($lob === quoteTypeCode::Car) {
            $query = $this->getCarQuoteQuery($lob);
            $query = $this->applyFiltersForCar($query, $filters);
        } else {
            $query = $this->getPersonsalQuoteQuery($lob);
            $query = $this->applyFilters($query, $filters);
        }
        $query = $query->get();

        // map operation to calculate gross and net conversions of records
        return $query->map(function ($row) {
            $netDenominator = $row->total_leads - $row->bad_leads;
            $grossDenominator = $row->total_leads;
            $row->net_conversion = (float) $netDenominator > 0 ? round(($row->sale_leads / $netDenominator) * 100, 2) : 0;
            $row->gross_conversion = (float) $grossDenominator > 0 ? round(($row->sale_leads / $grossDenominator) * 100, 2) : 0;

            return $row;
        });
    }

    private function getCarQuoteQuery($lob)
    {
        $query = CarQuote::query()
            ->select(
                'users.id as advisorId',
                DB::raw('DATE_FORMAT(quote_batches.start_date, "%d-%m-%Y") as start_date'),
                DB::raw('DATE_FORMAT(quote_batches.end_date, "%d-%m-%Y") as end_date'),
                'quote_batches.name as batch_name',
                'users.name as advisor_name',
                'quote_batches.id as quote_batch_id',
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->where('users.is_active', true)
            ->groupBy('car_quote_request.advisor_id', 'car_quote_request.quote_batch_id')
            ->orderBy('car_quote_request.quote_batch_id')->orderBy('users.email');

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
            $userIds = $this->walkTree(auth()->user()->id, $lob);
            if (auth()->user()->isManagerORDeputy()) {
                $userIds = UserManager::where('manager_id', auth()->user()->id)
                    ->get()
                    ->filter(function ($user) use ($userIds) {
                        return in_array($user->user_id, $userIds);
                    })
                    ->pluck('user_id')
                    ->toArray();
            }

            $query = $query->whereIn('car_quote_request.advisor_id', $userIds);
        }

        $this->addSelect($query, 'car_quote_request');

        return $query;
    }

    private function getAdvisorConversionQuoteStatusDate()
    {
        return cache()->remember('advisor_conversion_quote_status_date', now()->addHour(), function () {
            return Carbon::parse(getAppStorageValueByKey(ApplicationStorageEnums::ADVISOR_CONVERSION_QUOTE_STATUS_DATE));
        });
    }

    private function getApprovedStatuses()
    {
        return [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
        ];
    }

    private function getSaleStatuses()
    {
        return [
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
        ];
    }

    private function getBindings(string $table)
    {
        $excludedSources = implode(',', array_map(fn ($source) => "'$source'", $this->getExcludedSources()));

        return [
            ':table' => $table,
            ':excludedSources' => $excludedSources,
            ':quoteStatusDate' => $this->getAdvisorConversionQuoteStatusDate(),
            ':saleStatuses' => implode(',', $this->getSaleStatuses()),
            ':approvedStatuses' => implode(',', $this->getApprovedStatuses()),
            ':badLeadsStatuses' => implode(',', $this->getBadLeadStatuses()),
            ':imRenewal' => QuoteStatusEnum::IMRenewal,
            ':notInterestedStatuses' => implode(',', $this->getNotInterestedStatuses()),
            ':newLead' => QuoteStatusEnum::NewLead,
            ':inProgressStatuses' => implode(',', $this->getInProgressStatuses()),
            ':paidStatuses' => implode(',', $this->getPaidStatuses()),
        ];
    }

    private function addSelect($query, $table)
    {
        $getSaleLeadsQuery = function ($sourceCondition, $as) use ($table) {
            return strtr('SUM(CASE WHEN (
                            ((:table.payment_status_id in (:paidStatuses) OR :table.quote_status_id in (:approvedStatuses)) and :table.transaction_approved_at is NULL) OR
                            (:table.quote_status_id in (:approvedStatuses) and :table.transaction_approved_at < ":quoteStatusDate") OR
                            (:table.quote_status_id in (:saleStatuses) and :table.transaction_approved_at >= ":quoteStatusDate")
                        ) and :table.source '.$sourceCondition.' (:excludedSources) THEN 1 ELSE 0 END
                    ) as '.$as, $this->getBindings($table));
        };

        $query->addSelect(
            DB::raw(
                strtr('SUM(CASE WHEN :table.source NOT IN (:excludedSources) THEN 1 ELSE 0 END) as total_leads', $this->getBindings($table))
            ),
            DB::raw(
                strtr('SUM(CASE WHEN :table.quote_status_id = :newLead and :table.source NOT IN (:excludedSources) THEN 1 ELSE 0 END) as new_leads', $this->getBindings($table))
            ),
            DB::raw(
                strtr('SUM(CASE WHEN :table.quote_status_id in (:notInterestedStatuses) and :table.source NOT IN (:excludedSources) THEN 1 ELSE 0 END) as not_interested', $this->getBindings($table))
            ),
            DB::raw(
                strtr('SUM(CASE WHEN :table.quote_status_id in (:inProgressStatuses) and :table.source NOT IN (:excludedSources) THEN 1 ELSE 0 END) as in_progress', $this->getBindings($table))
            ),
            DB::raw(
                strtr('SUM(CASE WHEN :table.source IN (:excludedSources) THEN 1 ELSE 0 END) as manual_created', $this->getBindings($table))
            ),
            DB::raw(
                strtr('SUM(CASE WHEN :table.quote_status_id in (:badLeadsStatuses)  and :table.source NOT IN (:excludedSources) THEN 1 ELSE 0 END) as bad_leads', $this->getBindings($table))
            ),
            DB::raw($getSaleLeadsQuery('NOT IN', 'sale_leads')),
            DB::raw($getSaleLeadsQuery('IN', 'created_sale_leads')),
            DB::raw(
                strtr('SUM(CASE WHEN :table.quote_status_id = :imRenewal THEN 1 ELSE 0 END) and :table.source NOT IN (:excludedSources) as afia_renewals_count', $this->getBindings($table))
            ),
            DB::raw(
                strtr('SUM(CASE WHEN :table.quote_status_id in (:badLeadsStatuses) and :table.source IN (:excludedSources) THEN 1 ELSE 0 END) as manual_created_bad_leads', $this->getBindings($table))
            ),
        );
    }

    private function getPersonsalQuoteQuery($lob)
    {
        $lobFiltered = in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE]) ? quoteTypeCode::Business : $lob;
        $lobId = QuoteTypeRepository::where('code', $lobFiltered)->first();

        $query = PersonalQuote::query()
            ->select(
                'users.id as advisorId',
                DB::raw('DATE_FORMAT(quote_batches.start_date, "%d-%m-%Y") as start_date'),
                DB::raw('DATE_FORMAT(quote_batches.end_date, "%d-%m-%Y") as end_date'),
                'quote_batches.name as batch_name',
                'quote_batches.id as quote_batch_id',
                'users.name as advisor_name',
            )
            ->join('users', 'users.id', 'personal_quotes.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'personal_quotes.quote_batch_id')
            ->join('personal_quote_details', 'personal_quote_details.personal_quote_id', 'personal_quotes.id')
            ->where('personal_quotes.quote_type_id', $lobId->id)
            ->where('users.is_active', true)
            ->groupBy(
                'personal_quotes.advisor_id',
                'personal_quotes.quote_batch_id'
            )
            ->orderBy('personal_quotes.quote_batch_id')
            ->orderBy('users.email');
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
            $userIds = $this->walkTree(auth()->user()->id, $lob);
            if (auth()->user()->isManagerORDeputy()) {
                $userIds = UserManager::where('manager_id', auth()->user()->id)
                    ->get()
                    ->filter(function ($user) use ($userIds) {
                        return in_array($user->user_id, $userIds);
                    })
                    ->pluck('user_id')
                    ->toArray();
            }

            $query = $query->whereIn('personal_quotes.advisor_id', $userIds);
        }

        $this->addSelect($query, 'personal_quotes');

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

    public function getFilterOptions()
    {
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $advisors = [];
        $teams = [];

        $batches = QuoteBatches::query()
            ->select('name', 'start_date', 'end_date', 'id')
            ->orderBy('id')
            ->get()
            ->keyBy('id')
            ->map(function ($batch) {
                $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
                $start_date = Carbon::parse($batch->start_date)->format($dateFormat);
                $end_date = Carbon::parse($batch->end_date)->format($dateFormat);

                return $batch->name.'-('.$start_date.' to '.$end_date.')';
            })
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

        $vehicleCategories = $this->getVehicleTypes()->pluck('text')->map(function ($category) {
            return ['value' => ucwords($category), 'label' => ucwords(strtolower($category))];
        })->toArray();
        $vehicleType = [
            quoteTypeCode::Car => $vehicleCategories,
        ];

        return [
            'lob' => $lobs,
            'maxDays' => $maxDays,
            'batches' => $batches,
            'tiers' => $tiers,
            'leadSources' => $leadSources,
            'advisors' => $advisors,
            'teams' => $teams,
            'insurance_for' => $insuranceFor,
            'travel_coverage' => $travelCoverage,
            'insurance_type' => $insuranceType,
            'vehicle_type' => $vehicleType,
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

    private function applyLeadTypeFilter($query, $table, $filters)
    {
        $query->when(isset($filters->leadType), function ($subQuery) use ($filters, $table) {
            $quoteStatuses = match ($filters->leadType) {
                ReportsLeadTypeEnum::NEW_LEADS => [QuoteStatusEnum::NewLead],
                ReportsLeadTypeEnum::NOT_INTERESTED => $this->getNotInterestedStatuses(),
                ReportsLeadTypeEnum::IN_PROGRESS => $this->getInProgressStatuses(),
                ReportsLeadTypeEnum::BAD_LEAD => $this->getBadLeadStatuses(),
                ReportsLeadTypeEnum::AFIA_RENEWALS_COUNT => [QuoteStatusEnum::IMRenewal],
                default => [],
            };

            $subQuery->when(! empty($quoteStatuses), fn ($q) => $q->whereIn("{$table}.quote_status_id", $quoteStatuses))
                ->when(in_array($filters->leadType, [ReportsLeadTypeEnum::SALE_LEAD, ReportsLeadTypeEnum::CREATED_SALE_LEAD]), function ($q) use ($table) {
                    $q->where(function ($sq) use ($table) {
                        $sq->where(function ($nsq) use ($table) {
                            $nsq->whereNull("{$table}.transaction_approved_at")->where(function ($nssq) use ($table) {
                                $nssq->whereIn("{$table}.payment_status_id", $this->getPaidStatuses());
                                $nssq->orWhereIn("{$table}.quote_status_id", $this->getApprovedStatuses());
                            });
                        })->orWhere(function ($nsq) use ($table) {
                            $nsq->where("{$table}.transaction_approved_at", '<', $this->getAdvisorConversionQuoteStatusDate())->whereIn("{$table}.quote_status_id", $this->getApprovedStatuses());
                        })->orWhere(function ($nsq) use ($table) {
                            $nsq->where("{$table}.transaction_approved_at", '>=', $this->getAdvisorConversionQuoteStatusDate())->whereIn("{$table}.quote_status_id", $this->getSaleStatuses());
                        });
                    });
                })
                ->when(in_array($filters->leadType, [ReportsLeadTypeEnum::MANUAL_CREATED, ReportsLeadTypeEnum::CREATED_SALE_LEAD]),
                    fn ($q) => $q->whereIn("{$table}.source", $this->getExcludedSources()),
                    fn ($q) => $q->whereNotIn("{$table}.source", $this->getExcludedSources())
                );
        });
    }

    private function applyCarFilters($query, $lob, $filters)
    {
        $query->when($lob === quoteTypeCode::Car, function ($q) use ($filters) {
            $q->filterByTiers($filters?->tiersFilter)
                ->when((! empty($filters->vehicle_type) && $filters->vehicle_type != 'All') ||
                (isset($filters->isCommercial) && $filters->isCommercial != 'All'),
                    function ($sq) {
                        $sq->join('car_quote_request', 'car_quote_request.uuid', 'personal_quotes.uuid');
                    }
                )->when(! empty($filters->vehicle_type) && $filters->vehicle_type != 'All', function ($sq) use ($filters) {
                    $sq->join('vehicle_type', function ($join) use ($filters) {
                        $join->on('vehicle_type.id', 'car_quote_request.vehicle_type_id')->where('vehicle_type.category', $filters->vehicle_type);
                    });
                })->when(isset($filters->isCommercial) && $filters->isCommercial != 'All', function ($sq) use ($filters) {
                    $filters->isCommercial = $filters->isCommercial == 'true' ? true : false;
                    $sq->leftJoin('car_model', function ($join) use ($filters) {
                        $join->on('car_model.id', 'car_quote_request.car_model_id')->where('car_model.is_commercial', $filters->isCommercial);
                    });
                });
        });
    }

    private function applyTravelFilters($query, $lob, $filters)
    {
        $query->when($lob === quoteTypeCode::Travel, function ($q) use ($filters) {
            $isTravelQuote = (! empty($filters->insurance_type) && $filters->insurance_type != '') || (! empty($filters->travel_coverage) && $filters->travel_coverage != '');
            $q->when($isTravelQuote, function ($sq) {
                $sq->join('travel_quote_request', 'travel_quote_request.uuid', 'personal_quotes.uuid');
            })->when(! empty($filters->insurance_type) && $filters->insurance_type != '', function ($sq) use ($filters) {
                $sq->where('travel_quote_request.direction_code', $filters->insurance_type);
            })->when(! empty($filters->travel_coverage) && $filters->travel_coverage != '', function ($sq) use ($filters) {
                $sq->where('travel_quote_request.coverage_code', $filters->travel_coverage);
            })->when(isset($filters->isEmbeddedProducts) && $filters->isEmbeddedProducts == 'false', function ($sq) use ($isTravelQuote) {
                $table = $isTravelQuote ? 'travel_quote_request.source' : 'source';
                $sq->where($table, '!=', EmbeddedProductEnum::SRC_CAR_EMBEDDED_PRODUCT);
            });
        });
    }

    public function applyFilters($query, $filters, $isPopup = false)
    {
        $filters = (object) $filters;
        $lob = $filters->lob ?? '';

        [$freshLoad, $startDate, $endDate] = $this->getStartAndEndDate($filters);

        $query->filterByAdvisors($filters?->advisorId)
            ->filterByAdvisors($filters?->advisorsFilter)
            ->filterByBatches($filters->quoteBatchId)
            ->filterByBatches($filters?->batchNumberFilter)
            ->filterByTeams($filters?->teamsFilter)
            ->filterBySubTeams($filters?->subteams)
            ->when($lob === quoteTypeCode::Travel, function ($q) {
                $q->filterBySegment(request()->segment_filter, QuoteTypeId::Travel);
            })
            ->when($lob === quoteTypeCode::Health, function ($q) {
                $q->filterBySegment(request()->segment_filter, QuoteTypeId::Health);
            })
            ->when($lob === quoteTypeCode::Car, function ($q) {
                $q->filterBySegment(request()->segment_filter, QuoteTypeId::Car);
            })
            ->when($freshLoad || isset($filters->advisorAssignedDates), function ($q) use ($startDate, $endDate) {
                $q->whereBetween('personal_quote_details.advisor_assigned_date', [$startDate, $endDate]);
            })
            ->when(isset($filters->ecommerceFilter) && $filters->ecommerceFilter != 'All', function ($q) use ($filters) {
                $q->where('personal_quotes.is_ecommerce', $filters->ecommerceFilter == 'Yes');
            })
            ->when(isset($filters->excludeCreatedLeadsFilter) && $filters->excludeCreatedLeadsFilter == 'yes', function ($q) {
                $q->whereNotIn('personal_quotes.source', $this->getExcludedSources());
            })
            ->when(isset($filters->leadSourceFilter) && ! empty($filters->leadSourceFilter), function ($q) use ($filters) {
                $q->whereIn('personal_quotes.source', $filters->leadSourceFilter);
            }, function ($q) use ($isPopup) {
                $q->whereNotIn('personal_quotes.source', [LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO])
                    ->when($isPopup === true, fn ($sq) => $sq->whereNull('personal_quotes.renewal_import_code'));
            })
            ->when($lob === quoteTypeCode::Health, function ($q) use ($filters) {
                $q->when(! empty($filters->insurance_for) && $filters->insurance_for != '', function ($sq) use ($filters) {
                    $sq->join('health_quote_request', function ($join) use ($filters) {
                        $join->on('health_quote_request.uuid', 'personal_quotes.uuid')->where('health_quote_request.cover_for_id', $filters->insurance_for);
                    });
                });
            })
            ->when($lob === quoteTypeCode::Home, function ($q) use ($filters) {
                $q->when(! empty($filters->insurance_for) && $filters->insurance_for != '', function ($sq) use ($filters) {
                    $sq->join('home_quote_request', function ($join) use ($filters) {
                        $join->on('home_quote_request.uuid', 'personal_quotes.uuid')->where('home_quote_request.iam_possesion_type_id', $filters->insurance_for);
                    });
                });
            })
            ->when($lob === quoteTypeCode::Life, function ($q) use ($filters) {
                $q->when(! empty($filters->insurance_type) && $filters->insurance_type != '', function ($sq) use ($filters) {
                    $sq->join('life_quote_request', 'life_quote_request.uuid', 'personal_quotes.uuid')->where('life_quote_request.tenure_of_insurance_id', $filters->insurance_type);
                });
            })
            ->when($lob === quoteTypeCode::CORPLINE, function ($q) use ($filters) {
                $q->join('business_quote_request', 'business_quote_request.uuid', 'personal_quotes.uuid')
                    ->when(! empty($filters->insurance_type) && $filters->insurance_type != '', function ($sq) use ($filters) {
                        $sq->where('business_quote_request.business_type_of_insurance_id', $filters->insurance_type);
                    }, function ($sq) {
                        $sq->where('business_quote_request.business_type_of_insurance_id', '!=', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
                    });
            })
            ->when($lob === quoteTypeCode::GroupMedical, function ($q) {
                $q->join('business_quote_request', 'business_quote_request.uuid', 'personal_quotes.uuid')
                    ->where('business_quote_request.business_type_of_insurance_id', quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical));
            });

        $this->applyCarFilters($query, $lob, $filters);
        $this->applyTravelFilters($query, $lob, $filters);
        $this->applyLeadTypeFilter($query, 'personal_quotes', $filters);

        return $query;
    }

    public function applyFiltersForCar($query, $filters, $isPopup = false)
    {
        $filters = (object) $filters;

        [$freshLoad, $startDate, $endDate] = $this->getStartAndEndDate($filters);

        $query->filterByAdvisors($filters?->advisorId)
            ->filterByAdvisors($filters?->advisorsFilter)
            ->filterByBatches($filters->quoteBatchId)
            ->filterByBatches($filters?->batchNumberFilter)
            ->filterByTeams($filters?->teamsFilter)
            ->filterBySubTeams($filters?->subteams)
            ->filterByTiers($filters?->tiersFilter)
            ->filterBySegment()
            ->when($freshLoad || isset($filters->advisorAssignedDates), function ($q) use ($startDate, $endDate) {
                $q->whereBetween('car_quote_request_detail.advisor_assigned_date', [$startDate, $endDate]);
            })
            ->when(isset($filters->ecommerceFilter) && $filters->ecommerceFilter != 'All', function ($q) use ($filters) {
                $q->where('car_quote_request.is_ecommerce', $filters->ecommerceFilter == 'Yes');
            })
            ->when(isset($filters->isCommercial) && $filters->isCommercial != 'All', function ($q) use ($filters) {
                $filters->isCommercial = $filters->isCommercial == 'true';
                $q->where('car_model.is_commercial', $filters->isCommercial);
            })
            ->when(! empty($filters->vehicle_type) && $filters->vehicle_type != 'All', function ($q) use ($filters) {
                $q->join('vehicle_type', function ($join) use ($filters) {
                    $join->on('vehicle_type.id', 'car_quote_request.vehicle_type_id')->where('vehicle_type.category', $filters->vehicle_type);
                });
            })
            ->when(isset($filters->excludeCreatedLeadsFilter) && $filters->excludeCreatedLeadsFilter == 'yes', function ($q) {
                $q->whereNotIn('car_quote_request.source', $this->getExcludedSources());
            })
            ->when(isset($filters->leadSourceFilter) && ! empty($filters->leadSourceFilter), function ($q) use ($filters) {
                $q->whereIn('car_quote_request.source', $filters->leadSourceFilter);
            }, function ($q) use ($isPopup) {
                $q->whereNotIn('car_quote_request.source', [LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::SAPGO, LeadSourceEnum::SAPJO])
                    ->when($isPopup, function ($sq) {
                        $sq->whereNull('car_quote_request.renewal_import_code');
                    });
            });

        $this->applyLeadTypeFilter($query, 'car_quote_request', $filters);

        return $query;
    }

    public function getAdvisorsAssignedLeads($filters)
    {
        $lob = $filters['lob'] ?? quoteTypeCode::Car;
        if ($lob === quoteTypeCode::Car) {
            $query = $this->getCarQuoteAssignedLeadsQuery();
            $query = $this->applyFiltersForCar($query, $filters, true);
        } else {
            $query = $this->getPersonalQuoteAssignedLeadsQuery($lob);
            $query = $this->applyFilters($query, $filters, true);
        }

        return $query->paginate(10);
    }

    private function getCarQuoteAssignedLeadsQuery()
    {
        return CarQuote::query()
            ->select(
                DB::raw("CONCAT(car_quote_request.first_name, ' ', car_quote_request.last_name) as fullName"),
                'car_quote_request.code as cdbId',
                'quote_status.text as quoteStatusName'
            )
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->join('quote_status', 'quote_status.id', 'car_quote_request.quote_status_id')
            ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->orderBy('car_quote_request_detail.advisor_assigned_date', 'desc')
            ->where('users.is_active', true);
    }

    private function getPersonalQuoteAssignedLeadsQuery($lob)
    {
        $lob = in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE]) ? quoteTypeCode::Business : $lob;
        $lobId = QuoteTypeRepository::where('code', $lob)->first();

        return PersonalQuote::query()
            ->select(
                DB::raw("CONCAT(personal_quotes.first_name, ' ', personal_quotes.last_name) as fullName"),
                'personal_quotes.code as cdbId',
                'quote_status.text as quoteStatusName'
            )
            ->join('users', 'users.id', 'personal_quotes.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'personal_quotes.quote_batch_id')
            ->join('personal_quote_details', 'personal_quote_details.personal_quote_id', 'personal_quotes.id')
            ->join('quote_status', 'quote_status.id', 'personal_quotes.quote_status_id')
            ->where('personal_quotes.quote_type_id', $lobId->id)
            ->where('users.is_active', true)
            ->orderBy('personal_quote_details.advisor_assigned_date', 'desc');
    }
}
