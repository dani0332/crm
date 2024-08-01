<?php

namespace App\Services\Reports;

use App\Enums\EmbeddedProductEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
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
            'subeams' => $request->sub_teams,
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
        $extendedQuery = $query->map(function ($row) {
            $netDenominator = $row->total_leads - $row->bad_leads;
            $grossDenominator = $row->total_leads;
            $row->net_conversion = (float) $netDenominator > 0 ? round(($row->sale_leads / $netDenominator) * 100, 2) : 0;
            $row->gross_conversion = (float) $grossDenominator > 0 ? round(($row->sale_leads / $grossDenominator) * 100, 2) : 0;

            return $row;
        });

        return $extendedQuery;

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
                DB::raw('SUM(CASE WHEN car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as total_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::NewLead.' and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as new_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as not_interested'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.','.QuoteStatusEnum::PendingQuote.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.')  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(CASE WHEN (car_quote_request.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR car_quote_request.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as created_sale_leads'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id = '.QuoteStatusEnum::IMRenewal.' THEN 1 ELSE 0 END)  and car_quote_request.source != "'.LeadSourceEnum::IMCRM.'" as afia_renewals_count'),
                DB::raw('SUM(CASE WHEN car_quote_request.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') and car_quote_request.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
            )
            ->filterBySegment()
            ->join('users', 'users.id', 'car_quote_request.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'car_quote_request.quote_batch_id')
            ->join('car_quote_request_detail', 'car_quote_request_detail.car_quote_request_id', 'car_quote_request.id')
            ->leftJoin('car_make', 'car_make.id', '=', 'car_quote_request.car_make_id')
            ->leftJoin('car_model', 'car_model.id', '=', 'car_quote_request.car_model_id')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
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

        return $query;
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
                DB::raw('SUM(CASE WHEN personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as total_leads'),
                DB::raw('SUM(CASE WHEN personal_quotes.quote_status_id = '.QuoteStatusEnum::NewLead.' and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as new_leads'),
                DB::raw('SUM(CASE WHEN personal_quotes.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.')  and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as not_interested'),
                DB::raw('SUM(CASE WHEN personal_quotes.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.','.QuoteStatusEnum::PendingQuote.')  and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN personal_quotes.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN personal_quotes.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.')  and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN (personal_quotes.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR personal_quotes.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(CASE WHEN (personal_quotes.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR personal_quotes.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and personal_quotes.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as created_sale_leads'),
                DB::raw('SUM(CASE WHEN personal_quotes.quote_status_id = '.QuoteStatusEnum::IMRenewal.' THEN 1 ELSE 0 END)  and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" as afia_renewals_count'),
                DB::raw('SUM(CASE WHEN personal_quotes.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') and personal_quotes.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
            )
            ->join('users', 'users.id', 'personal_quotes.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'personal_quotes.quote_batch_id')
            ->join('personal_quote_details', 'personal_quote_details.personal_quote_id', 'personal_quotes.id')
            ->where('personal_quotes.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
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

        $lobs = array_filter($lobs, function ($permission) {
            return Auth::user()->can($permission);
        });

        $lobs = QuoteTypeRepository::GetList()
            ->filter(function ($lob) use ($lobs) {
                return array_key_exists($lob->code, $lobs);
            })
            ->pluck('code', 'text')
            ->toArray();

        if (Auth::user()->can(PermissionsEnum::CORPLINE_CONVERSION_REPORT)) {
            $lobs = array_merge(['CorpLine Insurance' => quoteTypeCode::CORPLINE], $lobs);
        }

        if (Auth::user()->can(PermissionsEnum::GROUPMEDICAL_CONVERSION_REPORT)) {
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
        $dropdownSourceService = new DropdownSourceService();

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

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;
        $lob = $filters->lob ?? '';

        if (isset($filters->advisorId)) {
            $query = $query->where('personal_quotes.advisor_id', $filters->advisorId);
        }

        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $batch = null;
        if (isset($filters->quoteBatchId)) {
            $batch = QuoteBatches::where('id', $filters->quoteBatchId)->first();
        }

        if (isset($filters->batchNumberFilter) && count($filters->batchNumberFilter) > 0) {
            $query = $query->whereIn('personal_quotes.quote_batch_id', $filters->batchNumberFilter);
        }

        if ($batch) {
            $query = $query->where('personal_quotes.quote_batch_id', $batch->id);
        }
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        $startDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat) :
                ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) :
                    now()->subDays((int) $maxDays)->startOfDay()->format($dateFormat));

        $endDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[1])->endOfDay()->format($dateFormat) :
            Carbon::parse(now())->endOfDay()->format($dateFormat);

        if ($freshLoad) {
            $query->whereBetween('personal_quote_details.advisor_assigned_date', [$startDate, $endDate]);
        } elseif (isset($filters->advisorAssignedDates)) {
            $query->whereBetween('personal_quote_details.advisor_assigned_date', [$startDate, $endDate]);
        }

        if (isset($filters->ecommerceFilter) && $filters->ecommerceFilter != 'All') {
            $query->where('personal_quotes.is_ecommerce', $filters->ecommerceFilter == 'Yes' ? 1 : 0);
        }
        if (isset($filters->excludeCreatedLeadsFilter)) {
            if ($filters->excludeCreatedLeadsFilter == 'yes') {
                info('inside excludeCreatedLeadsFilter');
                $query->where('personal_quotes.source', '!=', LeadSourceEnum::IMCRM);
            }
        }

        if (isset($filters->leadSourceFilter) && count($filters->leadSourceFilter) > 0) {
            $query->whereIn('personal_quotes.source', $filters->leadSourceFilter);
        }

        if (isset($filters->teamsFilter) && count($filters->teamsFilter) > 0) {
            $value = $filters->teamsFilter;
            $query->whereIn('users.id', function ($query) use ($value) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $value);
            });
        }

        if ((isset($filters->subeams) && count($filters->subeams) > 0)) {
            $value = $filters->subeams;
            $query->whereIn('users.id', function ($query) use ($value) {

                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->whereIn('sub_team_id', $value);
            });
        }

        if (isset($filters->advisorsFilter) && count($filters->advisorsFilter) > 0) {
            $query->whereIn('personal_quotes.advisor_id', $filters->advisorsFilter);
        }

        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::TOTAL_LEADS) {
            $query->where('personal_quotes.source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::NEW_LEADS) {
            $query->where('personal_quotes.quote_status_id', QuoteStatusEnum::NewLead)->where('personal_quotes.source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::NOT_INTERESTED) {
            $query->whereIn('personal_quotes.quote_status_id', [QuoteStatusEnum::PriceTooHigh, QuoteStatusEnum::PolicyPurchasedBeforeFirstCall, QuoteStatusEnum::NotInterested, QuoteStatusEnum::NotEligibleForInsurance, QuoteStatusEnum::NotLookingForMotorInsurance, QuoteStatusEnum::NonGccSpec, QuoteStatusEnum::AMLScreeningFailed])->where('personal_quotes.source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::IN_PROGRESS) {
            $query->whereIn('personal_quotes.quote_status_id', [QuoteStatusEnum::NotContactablePe, QuoteStatusEnum::FollowupCall, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer, QuoteStatusEnum::Quoted, QuoteStatusEnum::PaymentPending, QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::PendingQuote])->where('personal_quotes.source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::MANUAL_CREATED) {
            $query->where('personal_quotes.source', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::BAD_LEAD) {
            $query->whereIn('personal_quotes.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])->where('personal_quotes.source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::AFIA_RENEWALS_COUNT) {
            $query->where('personal_quotes.quote_status_id', QuoteStatusEnum::IMRenewal)->where('personal_quotes.source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::SALE_LEAD) {
            $query->where(function ($query) {
                $query->whereIn('personal_quotes.quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued])
                    ->orWhere('personal_quotes.payment_status_id', PaymentStatusEnum::CAPTURED);
            })->where('personal_quotes.source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::CREATED_SALE_LEAD) {
            $query->where(function ($query) {
                $query->whereIn('personal_quotes.quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued])
                    ->orWhere('personal_quotes.payment_status_id', PaymentStatusEnum::CAPTURED);
            })->where('personal_quotes.source', '=', LeadSourceEnum::IMCRM);
        }

        if ($lob === quoteTypeCode::Car) {

            if (isset($filters->tiersFilter) && count($filters->tiersFilter) > 0) {
                $query->whereIn('personal_quotes.tier_id', $filters->tiersFilter);
            }

            if ((! empty($filters->vehicle_type) && $filters->vehicle_type != 'All') ||
            (isset($filters->isCommercial) && $filters->isCommercial != 'All')) {
                $query->join('car_quote_request', 'car_quote_request.uuid', 'personal_quotes.uuid');
            }

            if (! empty($filters->vehicle_type) && $filters->vehicle_type != 'All') {
                $query->join('vehicle_type', function ($join) use ($filters) {
                    $join->on('vehicle_type.id', 'car_quote_request.vehicle_type_id')
                        ->where('vehicle_type.category', $filters->vehicle_type);
                });
            }

            if (isset($filters->isCommercial) && $filters->isCommercial != 'All') {
                $filters->isCommercial = $filters->isCommercial == 'true' ? true : false;
                $query->leftJoin('car_model', function ($join) use ($filters) {
                    $join->on('car_model.id', 'car_quote_request.car_model_id')
                        ->where('car_model.is_commercial', $filters->isCommercial);
                });
            }

            if (isset($filters->segment_filter) && $filters->segment_filter != 'all') {
                $query = $query->filterBySegment($filters->segment_filter, quoteTypeCode::Car);
            }
        }

        

        if ($lob === quoteTypeCode::Health) {
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
            if ((! empty($filters->insurance_type) && $filters->insurance_type != '') ||
                (! empty($filters->travel_coverage) && $filters->travel_coverage != '')) {
                $query->join('travel_quote_request', 'travel_quote_request.uuid', 'personal_quotes.uuid');
            }
            if (! empty($filters->insurance_type) && $filters->insurance_type != '') {
                $query->where('travel_quote_request.direction_code', $filters->insurance_type);
            }

            if (! empty($filters->travel_coverage) && $filters->travel_coverage != '') {
                $query->where('travel_quote_request.coverage_code', $filters->travel_coverage);
            }

            // TODO: confirm BA he wants include or only show report for embedded products
            if (isset($filters->isEmbeddedProducts) && $filters->isEmbeddedProducts == 'true' ) {
                $query->where('source', '=', EmbeddedProductEnum::SRC_CAR_EMBEDDED_PRODUCT);
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

    public function applyFiltersForCar($query, $filters)
    {
        $filters = (object) $filters;

        if (isset($filters->advisorId)) {
            $query = $query->where('car_quote_request.advisor_id', $filters->advisorId);
        }

        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $batch = null;
        if (isset($filters->quoteBatchId)) {
            $batch = QuoteBatches::where('id', $filters->quoteBatchId)->first();
        }

        if (isset($filters->batchNumberFilter) && count($filters->batchNumberFilter) > 0) {
            $query = $query->whereIn('car_quote_request.quote_batch_id', $filters->batchNumberFilter);
        }

        if ($batch) {
            $query = $query->where('car_quote_request.quote_batch_id', $batch->id);
        }
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        $startDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat) :
            ($freshLoad ? Carbon::parse(now())->startOfDay()->format($dateFormat) :
                now()->subDays((int) $maxDays)->startOfDay()->format($dateFormat));

        $endDate = isset($filters->advisorAssignedDates) ?
            Carbon::parse($filters->advisorAssignedDates[1])->endOfDay()->format($dateFormat) :
            Carbon::parse(now())->endOfDay()->format($dateFormat);

        if ($freshLoad) {
            $query->whereBetween('car_quote_request_detail.advisor_assigned_date', [$startDate, $endDate]);
        } elseif (isset($filters->advisorAssignedDates)) {
            $query->whereBetween('car_quote_request_detail.advisor_assigned_date', [$startDate, $endDate]);
        }

        if (isset($filters->ecommerceFilter) && $filters->ecommerceFilter != 'All') {
            $query->where('car_quote_request.is_ecommerce', $filters->ecommerceFilter == 'Yes' ? 1 : 0);
        }
        if (isset($filters->excludeCreatedLeadsFilter)) {
            if ($filters->excludeCreatedLeadsFilter == 'yes') {
                info('inside excludeCreatedLeadsFilter');
                $query->where('car_quote_request.source', '!=', LeadSourceEnum::IMCRM);
            }
        }
        if (isset($filters->tiersFilter) && count($filters->tiersFilter) > 0) {
            $query->whereIn('car_quote_request.tier_id', $filters->tiersFilter);
        }
        if (isset($filters->leadSourceFilter) && count($filters->leadSourceFilter) > 0) {
            $query->whereIn('car_quote_request.source', $filters->leadSourceFilter);
        }
        if (isset($filters->teamsFilter) && count($filters->teamsFilter) > 0) {
            $value = $filters->teamsFilter;
            $query->whereIn('users.id', function ($query) use ($value) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $value);
            });
        }

        if ((isset($filters->subeams) && count($filters->subeams) > 0)) {
            $value = $filters->subeams;
            $query->whereIn('users.id', function ($query) use ($value) {

                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->whereIn('sub_team_id', $value);
            });
        }

        if (isset($filters->advisorsFilter) && count($filters->advisorsFilter) > 0) {
            $query->whereIn('car_quote_request.advisor_id', $filters->advisorsFilter);
        }

        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::TOTAL_LEADS) {
            $query->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::NEW_LEADS) {
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::NewLead)->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::NOT_INTERESTED) {
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::PriceTooHigh, QuoteStatusEnum::PolicyPurchasedBeforeFirstCall, QuoteStatusEnum::NotInterested, QuoteStatusEnum::NotEligibleForInsurance, QuoteStatusEnum::NotLookingForMotorInsurance, QuoteStatusEnum::NonGccSpec, QuoteStatusEnum::AMLScreeningFailed])->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::IN_PROGRESS) {
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::NotContactablePe, QuoteStatusEnum::FollowupCall, QuoteStatusEnum::Interested, QuoteStatusEnum::NoAnswer, QuoteStatusEnum::Quoted, QuoteStatusEnum::PaymentPending, QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::PendingQuote])->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::MANUAL_CREATED) {
            $query->where('source', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::BAD_LEAD) {
            $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::AFIA_RENEWALS_COUNT) {
            $query->where('car_quote_request.quote_status_id', QuoteStatusEnum::IMRenewal)->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::SALE_LEAD) {
            $query->where(function ($query) {
                $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued])
                    ->orWhere('car_quote_request.payment_status_id', PaymentStatusEnum::CAPTURED);
            })->where('source', '!=', LeadSourceEnum::IMCRM);
        }
        if (isset($filters->leadType) && $filters->leadType == ReportsLeadTypeEnum::CREATED_SALE_LEAD) {
            $query->where(function ($query) {
                $query->whereIn('car_quote_request.quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued])
                    ->orWhere('car_quote_request.payment_status_id', PaymentStatusEnum::CAPTURED);
            })->where('source', '=', LeadSourceEnum::IMCRM);
        }

        if (isset($filters->isCommercial) && $filters->isCommercial != 'All') {
            $filters->isCommercial = $filters->isCommercial == 'true' ? true : false;
            $query->where('car_model.is_commercial', '=', $filters->isCommercial);
        }

        if (isset($filters->segment_filter) && $filters->segment_filter != 'all') {
            $query = $query->filterBySegment($filters->segment_filter, quoteTypeCode::Car);
        }

        if (! empty($filters->vehicle_type) && $filters->vehicle_type != 'All') {
            $query->join('vehicle_type', function ($join) use ($filters) {
                $join->on('vehicle_type.id', 'car_quote_request.vehicle_type_id')
                    ->where('vehicle_type.category', $filters->vehicle_type);
            });
        }

        return $query;
    }

    public function getAdvisorsAssignedLeads($filters)
    {
        $lob = $filters['lob'] ?? quoteTypeCode::Car;
        if ($lob === quoteTypeCode::Car) {
            $query = $this->getCarQuoteAssignedLeadsQuery();
            $query = $this->applyFiltersForCar($query, $filters);
        } else {
            $query = $this->getPersonalQuoteAssignedLeadsQuery($lob);
            $query = $this->applyFilters($query, $filters);
        }

        return $query->paginate(10);
    }

    private function getCarQuoteAssignedLeadsQuery()
    {
        $query = CarQuote::query()
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
            ->whereNull('car_quote_request.renewal_import_code')
            ->where('car_quote_request.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->orderBy('car_quote_request_detail.advisor_assigned_date', 'desc');

        return $query;
    }

    private function getPersonalQuoteAssignedLeadsQuery($lob)
    {
        $lob = in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE]) ? quoteTypeCode::Business : $lob;
        $lobId = QuoteTypeRepository::where('code', $lob)->first();

        $query = PersonalQuote::query()
            ->select(
                DB::raw("CONCAT(personal_quotes.first_name, ' ', personal_quotes.last_name) as fullName"),
                'personal_quotes.code as cdbId',
                'quote_status.text as quoteStatusName'
            )
            ->join('users', 'users.id', 'personal_quotes.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'personal_quotes.quote_batch_id')
            ->join('personal_quote_details', 'personal_quote_details.personal_quote_id', 'personal_quotes.id')
            ->join('quote_status', 'quote_status.id', 'personal_quotes.quote_status_id')
            ->whereNull('personal_quotes.renewal_import_code')
            ->where('personal_quotes.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->where('personal_quotes.quote_type_id', $lobId->id)
            ->orderBy('personal_quote_details.advisor_assigned_date', 'desc');

        return $query;
    }
}
