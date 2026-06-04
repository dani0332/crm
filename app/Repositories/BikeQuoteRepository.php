<?php

namespace App\Repositories;

use App\Enums\AMLStatusCode;
use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Facades\Ken;
use App\Models\BikeQuote;
use App\Models\InsuranceProvider;
use App\Models\PersonalQuote;
use App\Services\BranchAssignmentService;
use App\Services\DropdownSourceService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class BikeQuoteRepository extends BaseRepository
{
    use GenericQueriesAllLobs;

    public function model()
    {
        return PersonalQuote::class;
    }

    /**
     * create new personal quote
     *
     * @param  $quoteTypeCode
     * @return mixed
     */
    public function fetchCreate($data)
    {
        // Log sub-source parameters for Bike quotes
        LoggerService::info('Bike fetchCreate called with sub-source parameters', [
            'sub_source_id' => $data['sub_source_id'] ?? null,
            'sub_source_options_id' => $data['sub_source_options_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $quoteData = [
            'quoteTypeId' => intval(QuoteTypes::BIKE->id()),
            'nationalityId' => strval($data['nationality_id']),
            'mobileNo' => $data['mobile_no'],
            'email' => $data['email'],
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'dob' => $data['dob'],
            'assetValue' => $data['asset_value'] ?? null,
            'uaeLicenseHeldForId' => strval($data['uae_license_held_for_id']),
            'yearOfManufacture' => strval($data['year_of_manufacture']),
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => URL::current(),
            'createdById' => auth()->user()->id,
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->user()->id : null,
            'makeId' => $data['make_id'],
            'modelId' => $data['model_id'],
            'seatCapacity' => $data['seat_capacity'],
            'bikeValue' => $data['bike_value_tier'],
            'bikeValueTier' => $data['bike_value_tier'],
            'emirateOfRegistrationId' => $data['emirate_of_registration_id'],
            'bikeTypeInsuranceId' => $data['insurance_type_id'],
            'claimHistoryId' => $data['claim_history_id'],
            'hasNcdSupportingDocuments' => $data['has_ncd_supporting_documents'],
            'backHomeLicenseHeldForId' => $data['back_home_license_held_for_id'],
            'currentlyInsuredWithId' => $data['currently_insured_with'],
            'cubicCapacity' => $data['cubic_capacity'],
            'gender' => $data['gender'] ?? null,
            'chassisNumber' => $data['chassis_number'] ?? null,
            // Sub-source fields
            'subSourceId' => $data['sub_source_id'] ?? null,
            'subSourceOptionsId' => $data['sub_source_options_id'] ?? null,
            'additionalNotes' => $data['notes'] ?? null,
        ];

        return Capi::request('/api/v1-save-bike-quote', 'post', $quoteData);
    }

    /**
     * @return mixed
     */
    public function fetchUpdate($uuid, $data)
    {
        // Log sub-source parameters for Bike quote updates
        LoggerService::info('Bike fetchUpdate called with sub-source parameters', [
            'sub_source_id' => $data['sub_source_id'] ?? null,
            'sub_source_options_id' => $data['sub_source_options_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::BIKE->id())->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, [
                'first_name', 'last_name', 'email', 'mobile_no', 'dob', 'nationality_id', 'gender',
                'sub_source_id', 'sub_source_options_id', 'notes',
            ]);

            $quoteData['currently_insured_with_id'] = $data['currently_insured_with'];

            $quoteData['updated_by_id'] = Auth::user()->id;
            $quote->update($quoteData);

            $quote->bikeQuote()->updateOrCreate(
                ['personal_quote_id' => $quote->id],
                Arr::only($data, (new BikeQuote)->allowedColumns())
            );

            $quote->customer()->update([
                'dob' => $data['dob'] ?? null,
                'nationality_id' => $data['nationality_id'] ?? null,
                'gender' => $data['gender'] ?? null]
            );

            return $quote;
        });
    }

    /**
     * get all dropdown options required for form
     *
     * @return array
     */
    public function fetchGetFormOptions()
    {
        $dropdownSourceList = ['back_home_license_held_for_id', 'claim_history_id', 'car_type_insurance_id', 'emirate_of_registration_id', 'bike_make_id', 'bike_model_id', 'currently_insured_with_id'];
        $dropdownSource = [];
        foreach ($dropdownSourceList as $value) {
            $data = (new DropdownSourceService)->getDropdownSource($value);
            $dropdownSource[$value] = $data;
        }

        return [
            'nationalities' => NationalityRepository::withActive()->get(),
            'uaeLicenses' => UaeLicenseHeldRepository::withActive()->get(),
            'yearOfManufacture' => YearOfManufactureRepository::select('text as id', 'text')->orderBy('sort_order')->get(),
            'dropdownSource' => $dropdownSource,
        ];
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        $quoteTypeId = QuoteTypes::BIKE->id();
        $quote = $this->byQuoteTypeId($quoteTypeId)
            ->where($column, $value)
            ->with([
                'previousQuote:id,uuid,code',
                'bikeQuote' => function ($q) {
                    $q->with(['uaeLicenseHeldFor', 'bikeQuoteRequestDetail', 'backHomeLicenseHeldFor', 'bikeMake', 'bikeModel', 'carTypeInsurance', 'claimHistory', 'emirates']);
                },
                'advisor',
                'advisor.primaryBranch',
                'nationality',
                'quoteDetail.lostReason',
                'previousAdvisor',
                'currentlyInsuredWith',
                'transactionType',
                'insuranceProvider',
                'latestInsured' => function ($q) use ($quoteTypeId) {
                    $q->where('customer_insured.quote_type_id', $quoteTypeId);
                },
                'latestInsured.insuredKyc:id,insured_id',
                'payments' => function ($q) {
                    $q->with([
                        'paymentStatus',
                        'personalPlan',
                        'paymentMethod',
                        'paymentStatusLogs',
                        'insuranceProvider',
                        'paymentable',
                        'paymentSplits.paymentStatus',
                        'paymentSplits.paymentMethod',
                        'paymentSplits.verifiedByUser',
                        'paymentSplits.documents',
                        'paymentSplits.processJob',
                        'paymentSplits.paymentCharges',
                    ]);
                },
                'paymentStatus',
                'plans.insuranceProvider',
                'carPlan',
                'carPlan.insuranceProvider',
                'createdBy',
                'updatedBy',
                'customer.additionalContactInfo',
                'documents' => function ($q) {
                    $q->with('createdBy')->orderBy('created_at', 'desc');
                },
                'quoteRequestEntityMapping' => function ($entityMapping) {
                    $entityMapping->with('entity');
                },
                'branch:id,name',
            ])
            ->select([
                $this->getTable().'.*',
                'policy_expiry_date',
                'policy_start_date',
                'policy_issuance_date',
                DB::raw('YEAR(CURDATE()) - YEAR('.$this->getTable().'.dob) AS customer_age'),
            ])
            ->firstOrFail();

        $quote->customer_type = $quote->latestInsured?->customer_type ?? CustomerTypeEnum::Individual;
        $quote->payments->each->setAppends(['allow', 'copy_link_button', 'edit_button', 'approve_button', 'approved_button']);

        $data = ! empty($quote) ? $quote->toArray() : [];
        $quote->lost_reason = $data['quote_detail']['lost_reason']['text'] ?? null;
        $quote->previous_advisor_id_text = $quote->previousAdvisor?->name;
        $quote->transaction_type_text = $data['transaction_type']['text'] ?? null;
        if (isset($data['latestInsured'])) {
            $quote->emirates_id_number = $data['latestInsured']['id_type'] == 'emiratesId' ? $data['latestInsured']['id_number'] : null;
        }

        $quote->branch_name = ! $quote->is_branch_applicable ? 'N/A' : ($quote?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($quote?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Bike));

        return $quote;
    }

    /**
     * @return mixed
     */
    public function fetchGetData($forExport = false, $forTotalLeadsCount = false, $requestParams = [])
    {
        if (! Auth::check()) {
            $user = $requestParams['user'] ?? null;
            unset($requestParams['user']);
            Auth::login($user);
            DB::setDefaultConnection('mysql_read');
            request()->merge($requestParams);
        }

        $query = $this->byQuoteTypeCode(QuoteTypes::BIKE)->with([
            'quoteStatus',
            'currentlyInsuredWith',
            'advisor',
            'advisor.primaryBranch',
            'paymentStatus',
            'payments',
            'nationality',
            'renewalBatchModel',
            'latestInsured' => function ($q) {
                $q->where('customer_insured.quote_type_id', QuoteTypes::BIKE->id());
            },
            'customer',
            'subSource',
            'branch:id,name',
        ])
            ->when(auth()->user() && auth()->user()->hasRole(RolesEnum::BikeAdvisor), function ($query) {
                $query->where('advisor_id', auth()->id());
            })
            ->filter(! $forExport, $forTotalLeadsCount)
            ->filterByPrivateClient(request('private_client'))
            ->withFakeLeadCriteria()
            ->select([
                '*',
                DB::raw('
                    CASE
                        WHEN insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningPending.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningPending).'"
                        WHEN insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningCleared.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningCleared).'"
                        WHEN insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningFailed.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningFailed).'"
                        WHEN insurer_aml_status IS NULL THEN "'.AMLStatusCode::InsurerAMLScreeningNA.'"
                        ELSE insurer_aml_status
                    END AS insurer_aml_status_display
                '),
            ]);

        $this->adjustQueryByInsurerInvoiceFilters($query);
        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        // Apply authorize_date filter
        $query->when(! empty($this->getFilterValue('authorize_date', $requestParams)), function ($q) use ($requestParams) {
            $authorizeDates = $this->getFilterValue('authorize_date', $requestParams);
            if (is_array($authorizeDates) && count($authorizeDates) >= 2) {
                $startDate = Carbon::parse($authorizeDates[0])->startOfDay();
                $endDate = Carbon::parse($authorizeDates[1])->endOfDay();
                $q->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate) {
                    $paymentQuery->whereBetween('authorized_at', [$startDate, $endDate]);
                });
            }
        });

        // Apply captured_date filter
        $query->when(! empty($this->getFilterValue('captured_date', $requestParams)), function ($q) use ($requestParams) {
            $capturedDates = $this->getFilterValue('captured_date', $requestParams);
            if (is_array($capturedDates) && count($capturedDates) >= 2) {
                $startDate = Carbon::parse($capturedDates[0])->startOfDay();
                $endDate = Carbon::parse($capturedDates[1])->endOfDay();
                $q->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate) {
                    $paymentQuery->whereBetween('captured_at', [$startDate, $endDate]);
                });
            }
        });

        $query->orderBy('personal_quotes.'.($this->getFilterValue('sortBy', $requestParams) ?? 'created_at'), $this->getFilterValue('sortType', $requestParams) ?? 'desc');

        return $query;
    }

    public function fetchPostProcessBikeQuote($quotes)
    {
        return $quotes->map(function ($item) {
            $item->branch_name = ! $item->is_branch_applicable ? 'N/A' : ($item?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($item?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Bike));

            return $item;
        });
    }

    /**
     * Get filter value from requestParams or request object.
     */
    private function getFilterValue($filterName, $requestParams = [])
    {
        // First check if we have requestParams (for export context)
        if (! empty($requestParams) && isset($requestParams[$filterName])) {
            return $requestParams[$filterName];
        }

        // Fallback to request object
        return request($filterName);
    }

    /**
     * Check if filter value exists in requestParams or request object.
     */
    private function hasFilterValue($filterName, $requestParams = [])
    {
        // First check if we have requestParams (for export context)
        if (! empty($requestParams) && isset($requestParams[$filterName])) {
            $value = $requestParams[$filterName];

            return ! empty($value) || (is_array($value) && count($value) > 0);
        }

        // Fallback to request object
        return request()->filled($filterName);
    }

    public function fetchExport()
    {
        return $this->byQuoteTypeCode(QuoteTypes::BIKE)->with(['quoteStatus', 'currentlyInsuredWith', 'advisor', 'previousAdvisor', 'nationality', 'customer'])
            ->filter(paginate: false)
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc');
    }

    public function fetchPersonalQuotesData($uuid)
    {
        $this->query = DB::table('personal_quotes as pqr')
            ->select(
                'pqr.code',
                'pqr.source',
                'pqr.created_at',
                'pqr.updated_at',
                'pqr.device',
                'pqr.is_ecommerce',
                'pqr.first_name',
                'pqr.last_name',
                'pqr.mobile_no',
                'pqr.email',
                'n.TEXT AS nationality_id_text',
                'pqr.dob_formatted',
                'pqr.uuid',
                'u.name  as advisor_name',
                'cru.email as created_by',
                'upd.email as updated_by',
            )
            ->leftJoin('users as u', 'u.id', '=', 'pqr.advisor_id')
            ->leftJoin('users as cru', 'cru.id', '=', 'pqr.created_by_id')
            ->leftJoin('users as upd', 'upd.id', '=', 'pqr.updated_by_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'cqr.nationality_id');

        return $this->query->where('pqr.uuid', $uuid)->first();
    }

    public function fetchBikeAssumptionsUpdateProcess($request)
    {
        $updateQuote = BikeQuote::where('personal_quote_id', $request->bike_id)->first();

        if (! $updateQuote) {
            return null;
        }

        $updateQuote->fill($request->only([
            'cubic_capacity',
            'seat_capacity',
            'vehicle_type_id',
            'is_modified',
            'is_bank_financed',
            'is_gcc_standard',
            'current_insurance_status',
            'year_of_first_registration',
        ]));
        $updateQuote->save();

        return $updateQuote->id;
    }

    public function fetchBikeQuotePlanAddons($id)
    {
        return DB::table('bike_quote_request')
            ->select(
                'ca.text AS car_addon_text',
                'cao.value AS car_addon_option_value',
                'cao.price AS car_addon_option_price',
                'bqra.price AS bike_quote_request_addon_price',
                'ca.type AS car_addon_type'
            )
            ->join('bike_quote_request_addon as bqra', 'bike_quote_request.id', '=', 'bqra.quote_request_id')
            ->join('car_addon_option as cao', 'bqra.addon_option_id', '=', 'cao.id')
            ->join('car_addon as ca', 'cao.addon_id', '=', 'ca.id')
            ->where('bike_quote_request.uuid', $id)
            ->get();
    }

    public function fetchChangeInsurer($data)
    {
        $provider = InsuranceProvider::where('code', $data['provider_code'])->first();

        $requestData = [
            'quoteUuid' => $data['uuid'],
            'providerId' => $provider->id,
            'planId' => $data['plan_id'],
            'userId' => strval(auth()->id()),
        ];

        info('fn: changeInsurer sending change insurer request for quote UUID: '.$data['uuid'].' providerCode: '.$data['provider_code'].' planId: '.$data['plan_id']);

        return Ken::request('/update-bike-ecom-insurer', 'post', $requestData);
    }
}
