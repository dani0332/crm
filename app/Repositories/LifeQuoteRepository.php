<?php

namespace App\Repositories;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\LifeQuote;
use App\Traits\CentralTrait;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class LifeQuoteRepository extends BaseRepository
{
    use CentralTrait;

    public function model()
    {
        return LifeQuote::class;
    }

    public function fetchCreate($data)
    {
        $lifeData = [
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'email' => $data['email'],
            'mobileNo' => $data['mobile_no'],
            'dob' => $data['dob'],
            'sumInsuredValue' => $data['sum_insured_value'],
            'nationalityId' => $data['nationality_id'],
            'sumInsuredCurrencyId' => $data['sum_insured_currency_id'],
            'maritalStatusId' => $data['marital_status_id'],
            'purposeOfInsuranceId' => $data['purpose_of_insurance_id'],
            'childrenId' => $data['children_id'],
            'premium' => $data['premium'],
            'tenureOfInsuranceId' => $data['tenure_of_insurance_id'],
            'numberOfYearsId' => $data['number_of_years_id'],
            'isSmoker' => $data['is_smoker'] == 1 ? 1 : 0,
            'gender' => $data['gender'],
            'othersInfo' => $data['others_info'],
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => config('constants.APP_URL'),
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->user()->id : null,
        ];

        $response = Capi::request('/api/v1-save-life-quote', 'post', $lifeData);

        if (isset($response->quoteUID)) {
            // todo: make sure if this is required to update, or api is handling this as well
            $quote = $this->where('uuid', $response->quoteUID)->firstOrFail();
            $quote->update(['premium' => $lifeData['premium']]);
        }

        return $response;
    }

    public function fetchUpdate($uuid, $data)
    {
        $quote = $this->where('uuid', $uuid)->firstOrFail();

        $quoteData = Arr::only($data, [
            'first_name', 'last_name', 'email', 'mobile_no', 'dob', 'sum_insured_value', 'nationality_id', 'sum_insured_currency_id', 'marital_status_id', 'purpose_of_insurance_id', 'children_id', 'premium', 'tenure_of_insurance_id', 'number_of_years_id', 'is_smoker', 'gender', 'others_info',
        ]);
        $quote->update($quoteData);

        return $quote;
    }

    public function fetchGetData()
    {
        $query = $this->with(['advisor', 'quoteStatus', 'nationality', 'lifeQuoteRequestDetail.lostReason',
            'renewalBatchModel', 'lifeQuoteRequestDetail', 'paymentStatus',
            'payments'])
            ->when(\auth()->user()->hasRole(RolesEnum::LifeAdvisor), function ($query) {
                $query->where('advisor_id', \auth()->user()->id);
            })
            ->when(! empty(request()->advisor_assigned_date), function ($query) {
                $dateArray = request()->advisor_assigned_date;
                $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
                $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
                $query->whereHas('lifeQuoteRequestDetail', function ($subQuery) use ($dateFrom, $dateTo) {
                    $subQuery->whereBetween('advisor_assigned_date', [$dateFrom, $dateTo]);
                });
            })
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('life_quote_request.created_at', 'desc');

        $this->adjustQueryByInsurerInvoiceFilters($query);

        $this->adjustQueryByDateFilters($query, 'life_quote_request');

        return $query->simplePaginate()->withQueryString();
    }

    public function fetchExport()
    {
        return $this->with(['advisor', 'quoteStatus', 'nationality'])
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc');
    }

    public function fetchGetBy($column, $value)
    {
        $quote = $this->where($column, $value)->with(['advisor', 'quoteStatus', 'nationality', 'previousAdvisor', 'lifeQuoteRequestDetail.lostReason',
            'purposeOfInsurance', 'children', 'currency', 'insuranceTenure', 'numberOfYears', 'maritalStatus',
            'paymentStatus', 'customer.additionalContactInfo', 'transactionType', 'insuranceProvider',
            'payments.paymentMethod', 'payments.paymentStatus', 'payments.paymentSplits.paymentStatus', 'payments.paymentSplits.paymentMethod', 'payments.paymentSplits.documents', 'payments.paymentSplits.verifiedByUser', 'payments.paymentSplits.processJob',
            'quoteRequestEntityMapping' => function ($entityMapping) {
                $entityMapping->with('entity');
            },
        ])
            ->with([
                'documents' => function ($q) {
                    $q->with('createdBy')->orderBy('created_at', 'desc');
                },
            ])
            ->with([
                'payments.paymentSplits' => function ($q) {
                    $q->orderBy('sr_no', 'asc');
                },
            ])
            ->select([
                'life_quote_request.*',
                'policy_expiry_date',
                'policy_start_date',
                'policy_issuance_date',
                \DB::raw('IF(EXISTS (
                    SELECT *
                    FROM quote_request_entity_mapping
                    WHERE quote_type_id = '.QuoteTypeId::Life.' AND quote_request_id = life_quote_request.id),
                    "'.CustomerTypeEnum::Entity.'", "'.CustomerTypeEnum::Individual.'")
                as customer_type'),
            ])->firstOrFail();

        $data = ! empty($quote) ? $quote->toArray() : [];
        $quote->lost_reason = $data['quote_detail']['lost_reason']['text'] ?? null;
        $quote->previous_advisor_id_text = $data['quote_detail']['previous_advisor']['name'] ?? null;
        $quote->transaction_type_text = $data['transaction_type']['text'] ?? null;

        return $quote;
    }

    /**
     * get all dropdown options required for form.
     *
     * @return array
     */
    public function fetchGetFormOptions()
    {
        return [
            'nationalities' => NationalityRepository::withActive()->get(),
            'currency' => CurrencyTypeRepository::withActive()->get(),
            'purposeOfInsurance' => PurposeOfInsuranceRepository::withActive()->get(),
            'maritalStatus' => MaritalStatusRepository::withActive()->get(),
            'children' => LifeChildrenRepository::withActive()->get(),
            'typeOfInsurance' => LifeInsuranceTenureRepository::withActive()->get(),
            'numberOfYears' => LifeNumberOfYearsRepository::withActive()->get(),

        ];
    }

    public function fetchGetDuplicateEntityByCode($code)
    {
        return $this->where('parent_duplicate_quote_id', $code)->first();
    }

    public function fetchExportData()
    {
        $query = $this->with(['advisor', 'quoteStatus', 'nationality', 'lifeQuoteRequestDetail.lostReason'])
            ->filter(false)
            ->withFakeLeadCriteria();
        $this->adjustQueryByDateFilters($query, 'life_quote_request');

        return $query->orderBy('life_quote_request.created_at', 'desc')
            ->get();
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::LIFE->value).'-quote', 'post', $dataArr);
    }
}
