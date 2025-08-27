<?php

namespace App\Repositories;

use App\Enums\AMLStatusCode;
use App\Enums\LookupsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\PersonalQuote;
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
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => URL::current(),
            'createdById' => auth()->user()->id,
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->user()->id : null,
        ];

        info('JetSki Quote Create :'.json_encode($quoteData));

        return Capi::request('/api/v1-save-personal-quote', 'post', $quoteData);
    }

    /**
     * @return mixed
     */
    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::JETSKI->id())->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, [
                'first_name', 'last_name', 'email', 'mobile_no',
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

        return $this->byQuoteTypeId($quoteTypeId)
            ->where($column, $value)
            ->with([
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
            'latestInsured' => function ($q) {
                $q->where('customer_insured.quote_type_id', QuoteTypes::JETSKI->id());
            },
            'customer',
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
        $query->when(!empty($this->getFilterValue('authorize_date', $requestParams)), function ($q) use ($requestParams) {
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
        $query->when(!empty($this->getFilterValue('captured_date', $requestParams)), function ($q) use ($requestParams) {
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
