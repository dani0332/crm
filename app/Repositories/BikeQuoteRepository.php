<?php

namespace App\Repositories;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class BikeQuoteRepository extends BaseRepository
{
    public function model()
    {
        return PersonalQuote::class;
    }

    /**
     * create new personal quote
     *
     * @param $quoteTypeCode
     * @return mixed
     */
    public function fetchCreate($data)
    {
        $quoteData = [
            'quoteTypeId' => intval(QuoteTypes::BIKE->id()),
            'nationalityId' => strval($data['nationality_id']),
            'mobileNo' => $data['mobile_no'],
            'email' => $data['email'],
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'dob' => $data['dob'],
            'bikeCompanyToInsure' => $data['bike_company_to_insure'],
            'assetValue' => $data['asset_value'],
            'currentlyInsuredWithId' => strval($data['currently_insured_with_id']),
            'uaeLicenseHeldForId' => strval($data['uae_license_held_for_id']),
            'yearOfManufactureId' => strval($data['year_of_manufacture']),
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => URL::current(),
            'createdById' => auth()->user()->id,
        ];

        if ( ! auth()->user()->hasRole(RolesEnum::Admin) ) {
            $quoteData['advisorId'] = auth()->user()->id;
        }

        info('bikeQuote:'.json_encode($quoteData));

        return Capi::request('/api/v1-save-personal-quote', 'post', $quoteData);
    }

    /**
     * @return mixed
     */
    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function () use ($uuid, $data) {
            $quote = $this->byQuoteTypeId(QuoteTypes::BIKE->id())->where('uuid', $uuid)->firstOrFail();

            $quoteData = Arr::only($data, [
                'first_name', 'last_name', 'email', 'mobile_no', 'dob', 'nationality_id',  'asset_value', 'currently_insured_with_id',
            ]);

            $quoteData['updated_by_id'] = Auth::user()->id;
            $quote->update($quoteData);

            $quote->bikeQuote->update(Arr::only($data, ['bike_company_to_insure', 'year_of_manufacture', 'uae_license_held_for_id']));

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
            'nationalities' => NationalityRepository::withActive()->get(),
            'uaeLicenses' => UaeLicenseHeldRepository::withActive()->get(),
            'yearOfManufacture' => YearOfManufactureRepository::get(),
            'insuranceProviders' => InsuranceProviderRepository::select('id', 'text')->orderBy('text', 'asc')->get(),
        ];
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        $quote = $this->byQuoteTypeId(QuoteTypes::BIKE->id())
            ->where($column, $value)
            ->with(['bikeQuote' => function ($q) {
                $q->with(['uaeLicenseHeldFor', 'currentlyInsuredWith']);
            }, 'advisor', 'nationality', 'quoteDetail.lostReason', 'payments' => function ($q) {
                $q->with(['paymentStatus', 'personalPlan', 'paymentMethod']);
            }, 'createdBy', 'updatedBy', 'customer.additionalContactInfo', 'documents' => function ($q) {
                $q->with('createdBy')->orderBy('created_at', 'desc');
            }])->firstOrFail();

        $quote->payments->each->setAppends(['allow', 'copy_link_button', 'edit_button', 'approve_button', 'approved_button']);

        return $quote;
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        return $this->byQuoteTypeCode(QuoteTypes::BIKE)->with(['quoteStatus', 'currentlyInsuredWith', 'advisor'])
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc')
            ->simplePaginate();
    }
}
