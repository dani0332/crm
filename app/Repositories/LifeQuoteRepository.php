<?php

namespace App\Repositories;

use App\Enums\QuoteStatusEnum;
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

    public function fetchCreate($request)
    {
        $dataArr = [
            'firstName' => $request['first_name'],
            'lastName' => $request['last_name'],
            'email' => $request['email'],
            'mobileNo' => $request['mobile_no'],
            'dob' => $request['dob'],
            'sumInsuredValue' => $request['sum_insured_value'],
            'nationalityId' => $request['nationality_id'],
            'sumInsuredCurrencyId' => $request['sum_insured_currency_id'],
            'maritalStatusId' => $request['marital_status_id'],
            'purposeOfInsuranceId' => $request['purpose_of_insurance_id'],
            'childrenId' => $request['children_id'],
            'premium' => $request['premium'],
            'tenureOfInsuranceId' => $request['tenure_of_insurance_id'],
            'numberOfYearsId' => $request['number_of_years_id'],
            'isSmoker' => $request['is_smoker'] == 1 ? 1 : 0,
            'gender' => $request['gender'],
            'othersInfo' => $request['others_info'],
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => config('constants.APP_URL'),
        ];
        if (! Auth::user()->hasRole(RolesEnum::Admin)) {
            $dataArr['advisorId'] = Auth::user()->id;
        }
        $response = Capi::request('/api/v1-save-life-quote', 'post', $dataArr);

        if (isset($response->quoteUID)) {
            $quote = $this->where('uuid', $response->quoteUID)->firstOrFail();

            $quote->update(['premium' => $request['premium']]);
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
        return $this->where('quote_status_id', '!=', QuoteStatusEnum::Fake)->with(['advisor', 'quoteStatus', 'nationality'])->filter()->orderBy('created_at', 'desc')->simplePaginate()->withQueryString();
    }

    public function fetchGetBy($column, $value)
    {
        return $this->where($column, $value)->with(['advisor', 'quoteStatus', 'nationality', 'lifeQuoteRequestDetail.lostReason',
            'purposeOfInsurance', 'childern', 'currency', 'insuranceTenure', 'numberOfYears', 'maritalStatus',
            'paymentStatus', 'customer.additionalContactInfo'])->firstOrFail();
    }

    /**
     * get all dropdown options required for form
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
}
