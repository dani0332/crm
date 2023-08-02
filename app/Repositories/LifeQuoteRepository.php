<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\LifeQuote;
use App\Traits\CentralTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

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
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin) ) ? auth()->user()->id : null
        ];

        $response = Capi::request('/api/v1-save-life-quote', 'post', $lifeData);

        if (isset($response->quoteUID)) {
            //todo: make sure if this is required to update, or api is handling this as well
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
        return $this->with(['advisor', 'quoteStatus', 'nationality'])
            ->when(\auth()->user()->hasRole(RolesEnum::LifeAdvisor), function ($query) {
                $query->where(function ($query) {
                    $query->where('advisor_id', \auth()->user()->id);
                });
            })
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc')
            ->simplePaginate()
            ->withQueryString();
    }

    public function fetchGetBy($column, $value)
    {
        return $this->where($column, $value)->with(['advisor', 'quoteStatus', 'nationality', 'lifeQuoteRequestDetail.lostReason',
            'purposeOfInsurance', 'childern', 'currency', 'insuranceTenure', 'numberOfYears', 'maritalStatus',
            'paymentStatus', 'customer.additionalContactInfo'])->firstOrFail();
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
            'childern' => LifeChildrenRepository::withActive()->get(),
            'typeOfInsurance' => LifeInsuranceTenureRepository::withActive()->get(),
            'numberOfYears' => LifeNumberOfYearsRepository::withActive()->get(),

        ];
    }

    public function fetchExportData()
    {
        return $this->with(['advisor', 'quoteStatus', 'nationality'])
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function fetchCreateDuplicate(array $dataArr): object
    {
        return Capi::request('/api/v1-save-'.strtolower(QuoteTypes::LIFE->value).'-quote', 'post', $dataArr);
    }
}
