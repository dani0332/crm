<?php

namespace App\Services;

use App\Enums\EmbeddedProductEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Enums\TiersEnum;
use App\Enums\TravelQuoteEnum;
use App\Models\PersonalQuote;
use App\Models\Team;
use App\Models\Tier;
use App\Models\UserManager;
use App\Repositories\QuoteTypeRepository;
use App\Traits\GetUserTreeTrait;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComprehensiveConversionDashboardService extends BaseService
{
    use GetUserTreeTrait;
    use TeamHierarchyTrait;

    public function getReportData($request)
    {
        $lob = $request->lob ?? quoteTypeCode::Car;
        $lobFiltered = in_array($lob, [quoteTypeCode::GroupMedical, quoteTypeCode::CORPLINE]) ? quoteTypeCode::Business : $lob;
        $lobId = QuoteTypeRepository::where('code', $lobFiltered)->first();

        $records = PersonalQuote::query()
            ->select(
                'users.id as advisorId',
                DB::raw('DATE_FORMAT(quote_batches.start_date, "%d-%m-%Y") as start_date'),
                DB::raw('DATE_FORMAT(quote_batches.end_date, "%d-%m-%Y") as end_date'),
                'quote_batches.name as batch_name',
                'users.name as advisor_name',
                'quote_batches.id as quote_batch_id',
                DB::raw('SUM(CASE WHEN personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as total_leads'),
                DB::raw('SUM(CASE WHEN personal_quotes.quote_status_id = '.QuoteStatusEnum::NewLead.' and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as new_leads'),
                DB::raw('SUM(CASE WHEN personal_quotes.quote_status_id in ('.QuoteStatusEnum::PriceTooHigh.', '.QuoteStatusEnum::PolicyPurchasedBeforeFirstCall.', '.QuoteStatusEnum::NotInterested.', '.QuoteStatusEnum::NotEligibleForInsurance.', '.QuoteStatusEnum::NotLookingForMotorInsurance.', '.QuoteStatusEnum::NonGccSpec.','.QuoteStatusEnum::AMLScreeningFailed.')  and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as not_interested'),
                DB::raw('SUM(CASE WHEN personal_quotes.quote_status_id in ('.QuoteStatusEnum::NotContactablePe.', '.QuoteStatusEnum::FollowupCall.', '.QuoteStatusEnum::Interested.', '.QuoteStatusEnum::NoAnswer.', '.QuoteStatusEnum::Quoted.', '.QuoteStatusEnum::PaymentPending.','.QuoteStatusEnum::AMLScreeningCleared.','.QuoteStatusEnum::PendingQuote.')  and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as in_progress'),
                DB::raw('SUM(CASE WHEN personal_quotes.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created'),
                DB::raw('SUM(CASE WHEN personal_quotes.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.')  and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as bad_leads'),
                DB::raw('SUM(CASE WHEN (personal_quotes.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR personal_quotes.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.'))  and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as sale_leads'),
                DB::raw('SUM(CASE WHEN (personal_quotes.payment_status_id = "'.PaymentStatusEnum::CAPTURED.'"  OR personal_quotes.quote_status_id in ('.QuoteStatusEnum::TransactionApproved.','.QuoteStatusEnum::PolicyIssued.')) and personal_quotes.source = "'.LeadSourceEnum::IMCRM.'"  THEN 1 ELSE 0 END) as created_sale_leads'),
                DB::raw('SUM(CASE WHEN personal_quotes.quote_status_id = '.QuoteStatusEnum::IMRenewal.' THEN 1 ELSE 0 END)  and personal_quotes.source != "'.LeadSourceEnum::IMCRM.'" as afia_renewals_count'),
                DB::raw('SUM(CASE WHEN personal_quotes.quote_status_id in ('.QuoteStatusEnum::Duplicate.','.QuoteStatusEnum::Fake.') and personal_quotes.source = "'.LeadSourceEnum::IMCRM.'" THEN 1 ELSE 0 END) as manual_created_bad_leads'),
            )
            ->join('users', 'users.id', 'personal_quotes.advisor_id')
            ->join('quote_batches', 'quote_batches.id', 'personal_quotes.quote_batch_id')
            ->where('personal_quotes.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->where('users.is_active', true)
            ->where('personal_quotes.quote_type_id', $lobId->id)
            ->groupBy('personal_quotes.advisor_id', 'personal_quotes.quote_batch_id')
            ->orderByDesc('quote_batches.start_date')->orderBy('users.email');

        if (
            ! auth()->user()->hasAnyRole([
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ]) && auth()->user()->isManagerORDeputy()
        ) {

            $userIds = $this->walkTree(auth()->user()->id, $lob);
            $userIds = UserManager::where('manager_id', auth()->user()->id)
                ->get()
                ->filter(function ($user) use ($userIds) {
                    return in_array($user->user_id, $userIds);
                })
                ->pluck('user_id')
                ->toArray();
            $records = $records->whereIn('personal_quotes.advisor_id', $userIds);
        }

        $records = $this->applyFilters($records, $request->all());
        $records = $records->get();

        $labels = [];
        $data = [];

        $batchesWiseGroupedData = $records->groupBy('batch_name')->take(10)->sortKeys()->toArray();
        foreach ($batchesWiseGroupedData as $batchData) {
            $saleLeads = 0;
            $createdSaleLeads = 0;
            $totalLeads = 0;
            $badLeads = 0;
            $manualCreatedBadLeads = 0;
            foreach ($batchData as $record) {
                $saleLeads = $saleLeads + $record['sale_leads'];
                $createdSaleLeads = $createdSaleLeads + $record['created_sale_leads'];
                $totalLeads = $totalLeads + $record['total_leads'];
                $badLeads = $badLeads + $record['bad_leads'];
                $manualCreatedBadLeads = $manualCreatedBadLeads + $record['manual_created_bad_leads'];
            }
            $numerator = $saleLeads;
            $denominator = $totalLeads - $badLeads;
            $total = $denominator > 0 ? ($numerator / $denominator) : 0;

            $data[] = number_format((float) $total * 100, 2, '.', '');
            $labels[] = $record['batch_name'].'-('.$record['start_date'].' to '.$record['end_date'].')';
        }

        if (empty($data)) {
            $data[] = ['0.00'];
            $labels = [' '];
        }

        return [$labels, $data];
    }

    public function getFiltersByLob()
    {
        return [
            'teams' => [
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
            quoteTypeCode::Car => PermissionsEnum::COMPREHENSIVE_DASHBOARD_VIEW,
            quoteTypeCode::Bike => PermissionsEnum::BIKE_COMPREHENSIVE_DASHBOARD,
            quoteTypeCode::Health => PermissionsEnum::HEALTH_COMPREHENSIVE_DASHBOARD,
            quoteTypeCode::Travel => PermissionsEnum::TRAVEL_COMPREHENSIVE_DASHBOARD,
            quoteTypeCode::Pet => PermissionsEnum::PET_COMPREHENSIVE_DASHBOARD,
            quoteTypeCode::Cycle => PermissionsEnum::CYCLE_COMPREHENSIVE_DASHBOARD,
            quoteTypeCode::Yacht => PermissionsEnum::YACHT_COMPREHENSIVE_DASHBOARD,
            quoteTypeCode::Life => PermissionsEnum::LIFE_COMPREHENSIVE_DASHBOARD,
            quoteTypeCode::Home => PermissionsEnum::HOME_COMPREHENSIVE_DASHBOARD,
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

        if (Auth::user()->can(PermissionsEnum::CORPLINE_COMPREHENSIVE_DASHBOARD)) {
            $lobs = array_merge(['CorpLine Insurance' => quoteTypeCode::CORPLINE], $lobs);
        }

        if (Auth::user()->can(PermissionsEnum::GROUPMEDICAL_COMPREHENSIVE_DASHBOARD)) {
            $lobs = array_merge(['Group Medical Insurance' => quoteTypeCode::GroupMedical], $lobs);
        }

        return $lobs;
    }

    public function getFilterOptions()
    {
        $tiers = Tier::where('can_handle_tpl', 0)
            ->orderBy('name', 'asc')
            ->where('name', '!=', TiersEnum::TIER_R)
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($t) => $t->name)
            ->toArray();

        $lobs = $this->getLobByPermissions();
        $dropdownSourceService = new DropdownSourceService;

        $insuranceFor = [
            quoteTypeCode::Health => $dropdownSourceService->getDropdownSource('cover_for_id'),
            quoteTypeCode::Home => $dropdownSourceService->getDropdownSource('iam_possesion_type_id'),
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
                ['value' => TravelQuoteEnum::COVERAGE_CODE_SINGLE_TRIP, 'label' => 'Single Trip'],
                ['value' => TravelQuoteEnum::COVERAGE_CODE_MULTI_TRIP, 'label' => 'Multi Trip'],
                ['value' => TravelQuoteEnum::COVERAGE_CODE_ANNUAL_TRIP, 'label' => 'Annual Trip'],
            ],
            quoteTypeCode::Life => $lifeInsuranceType,
            quoteTypeCode::CORPLINE => $businessInsuranceType,
        ];

        return [
            'lob' => $lobs,
            'tiers' => $tiers,
            'insurance_for' => $insuranceFor,
            'insurance_type' => $insuranceType,
        ];
    }

    public function getDefaultFilters()
    {
        $lobs = $this->getLobByPermissions();
        $organicTeam = strval(Team::where('name', 'Organic')->first()->id);

        return [
            'lob' => reset($lobs),
            'isCommercial' => 'All',
            'car_team_id' => $organicTeam,
        ];
    }

    public function applyFilters($query, $filters)
    {
        $filters = (object) $filters;
        $lob = $filters->lob ?? '';

        if (in_array($lob, [quoteTypeCode::Car])) {
            if (isset($filters->tiers) && $filters->tiers != 'undefined') {
                $tiers = $filters->tiers;
            } else {
                $tiers = Tier::where('can_handle_tpl', 0)
                    ->orderBy('name', 'asc')
                    ->where('name', '!=', TiersEnum::TIER_R)
                    ->where('is_active', 1)
                    ->get()
                    ->pluck('id');
            }

            $query->join('tiers', function ($join) use ($tiers) {
                $join->on('tiers.id', 'personal_quotes.tier_id')
                    ->whereIn('tiers.id', $tiers);
            });
        }

        if (isset($filters->teams) && $filters->teams != 'undefined') {

            $query->whereIn('users.id', function ($query) use ($filters) {
                $query->distinct()
                    ->select('users.id')
                    ->from('users')
                    ->join('user_team', 'user_team.user_id', 'users.id')
                    ->join('teams', 'teams.id', 'user_team.team_id')
                    ->whereIn('teams.id', $filters->teams);
            });

            if (isset($filters->sub_teams) && $filters->sub_teams != 'undefined') {
                $query->whereIn('users.sub_team_id', $filters->sub_teams);
            }

        } else {

            if (in_array($lob, [quoteTypeCode::Car])) {
                $organicTeam = Team::where('name', 'Organic')->first();
                $query->whereIn('users.id', function ($query) use ($organicTeam) {
                    $query->distinct()
                        ->select('users.id')
                        ->from('users')
                        ->join('user_team', 'user_team.user_id', 'users.id')
                        ->join('teams', 'teams.id', 'user_team.team_id')
                        ->where('teams.id', $organicTeam->id);
                });
            }
        }

        if (isset($filters->advisors) && $filters->advisors != 'null') {
            $query = $query->whereIn('personal_quotes.advisor_id', $filters->advisors);
        }

        if ($lob === quoteTypeCode::Car) {

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
            if ((! empty($filters->insurance_type) && $filters->insurance_type != '')) {
                $query->join('travel_quote_request', 'travel_quote_request.uuid', 'personal_quotes.uuid');
                $query->where('travel_quote_request.coverage_code', $filters->insurance_type);

                if (isset($filters->isEmbeddedProducts) && $filters->isEmbeddedProducts == 'false') {
                    $query->where('travel_quote_request.source', '!=', EmbeddedProductEnum::SRC_CAR_EMBEDDED_PRODUCT);
                }
            } else {
                if (isset($filters->isEmbeddedProducts) && $filters->isEmbeddedProducts == 'false') {
                    $query->where('personal_quotes.source', '!=', EmbeddedProductEnum::SRC_CAR_EMBEDDED_PRODUCT);
                }
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
