<?php

namespace App\Repositories;

use App\Enums\AMLStatusCode;
use App\Enums\LookupsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Services\EACollaborateHelper;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class JetskiQuoteRepository extends BaseRepository
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
        // Log sub-source parameters
        LoggerService::info('JetskiQuoteRepository fetchCreate - Sub-source parameters', [
            'sub_source_id' => $data['sub_source_id'] ?? null,
            'sub_source_options_id' => $data['sub_source_options_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $quoteData = [
            'quoteTypeId' => intval(QuoteTypes::JETSKI->id()),
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'email' => $data['email'],
            'mobileNo' => $data['mobile_no'],
            'jetskiMake' => $data['jetski_make'],
            'jetskiModel' => $data['jetski_model'],
            'maxSpeed' => $data['max_speed'],
            'seatCapacity' => $data['seat_capacity'],
            'enginePower' => $data['engine_power'],
            'yearOfManufactureId' => strval($data['year_of_manufacture_id']),
            'jetskiMaterialId' => strval($data['jetski_material_id']),
            'jetskiUseId' => $data['jetski_use_id'],
            'claimHistory' => $data['claim_history'],
            'subSourceId' => $data['sub_source_id'],
            'subSourceOptionsId' => $data['sub_source_options_id'],
            'additionalNotes' => $data['notes'],
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => URL::current(),
            'createdById' => auth()->user()->id,
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->user()->id : null,
        ];

        EACollaborateHelper::applyEAIMCRMSource($quoteData);

        info('JetSki Quote Create :'.json_encode($quoteData));

        if (request()->input('ea_model')) {
            LoggerService::info('JetskiQuoteRepository: CAPI payload for EA lead', [
                'ea_model' => $quoteData['eaModel'] ?? request()->input('ea_model'),
                'source' => $quoteData['source'] ?? null,
                'email' => $quoteData['email'] ?? null,
                'lead_generator_id' => $quoteData['leadGeneratorId'] ?? null,
                'advisor_id' => $quoteData['advisorId'] ?? null,
                'quote_type_id' => $quoteData['quoteTypeId'] ?? null,
            ]);
        }

        $response = Capi::request('/api/v1-save-personal-quote', 'post', $quoteData);

        if (request()->input('ea_model')) {
            LoggerService::info('JetskiQuoteRepository: CAPI response for EA lead', [
                'ea_model' => request()->input('ea_model'),
                'quote_uid' => $response->quoteUID ?? null,
                'message' => $response->message ?? null,
                'has_errors' => ! empty($response->errors),
            ]);

            if (isset($response->quoteUID)) {
                $quote = PersonalQuote::where('uuid', $response->quoteUID)->first();
                if ($quote) {
                    EACollaborateHelper::dispatchLeadSubmittedEmail($quote, 'jetski');
                }
            }
        }

        return $response;
    }

    /**
     * @return mixed
     */
    public function fetchUpdate($uuid, $data)
    {
        // Log sub-source parameters for updates
        LoggerService::info('JetskiQuoteRepository fetchUpdate - Sub-source parameters', [
            'sub_source_id' => $data['sub_source_id'] ?? null,
            'sub_source_options_id' => $data['sub_source_options_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::JETSKI->id())->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, [
                'first_name', 'last_name', 'email', 'mobile_no', 'sub_source_id', 'sub_source_options_id', 'notes',
            ]);

            $quoteData['updated_by_id'] = Auth::user()->id;
            $quote->update($quoteData);

            $quote->jetskiQuote->update(Arr::only($data, ['jetski_make', 'jetski_model', 'year_of_manufacture_id', 'max_speed', 'seat_capacity',
                'engine_power', 'jetski_material_id', 'jetski_use_id', 'claim_history']));

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
        return [
            'jetski_materials' => LookupRepository::where('key', LookupsEnum::JETSKI_MATERIALS)->get(),
            'jetski_uses' => LookupRepository::where('key', LookupsEnum::JETSKI_USES)->get(),
            'yearOfManufacture' => YearOfManufactureRepository::get(),
        ];
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        $quoteTypeId = QuoteTypes::JETSKI->id();

        $quote = $this->byQuoteTypeId($quoteTypeId)
            ->where($column, $value)
            ->with([
                'previousQuote:id,uuid,code',
                'jetskiQuote',
                'nationality',
                'advisor',
                'quoteDetail.lostReason',
                'latestInsured' => function ($q) use ($quoteTypeId) {
                    $q->where('customer_insured.quote_type_id', $quoteTypeId);
                },
                'latestInsured.insuredKyc:id,insured_id',
                'payments' => function ($q) {
                    $q->with(['paymentStatus', 'personalPlan', 'paymentMethod', 'paymentable']);
                },
                'createdBy',
                'updatedBy',
                'customer.additionalContactInfo',
                'documents' => function ($q) {
                    $q->with('createdBy')->orderBy('created_at', 'desc');
                }])->firstOrFail();

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

        $query = $this->byQuoteTypeCode(QuoteTypes::JETSKI)->with([
            'quoteStatus',
            'currentlyInsuredWith',
            'advisor',
            'paymentStatus',
            'payments',
            'renewalBatchModel',
            'subSource',
            'latestInsured' => function ($q) {
                $q->where('customer_insured.quote_type_id', QuoteTypes::JETSKI->id());
            },
            'customer',
            'leadGenerator:id,name',
            'expertAdvisor:id,name',
        ])->when(auth()->user() && auth()->user()->hasRole(RolesEnum::JetskiAdvisor), function ($query) {
            $query->where('advisor_id', auth()->id());
        })
            ->when($this->hasFilterValue('advisor_assigned_date', $requestParams), function ($query) use ($requestParams) {
                $dateArray = $this->getFilterValue('advisor_assigned_date', $requestParams);
                $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
                $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
                $query->whereHas('quoteDetail', function ($subQuery) use ($dateFrom, $dateTo) {
                    $subQuery->whereBetween('advisor_assigned_date', [$dateFrom, $dateTo]);
                });
            })
            ->filter(! $forExport, $forTotalLeadsCount)
            ->filterByPrivateClient(request('private_client'))
            ->withFakeLeadCriteria($forTotalLeadsCount)
            ->filterBy('ea_model')
            ->filterByLeadGeneratorName(request('lead_generator'))
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

}
