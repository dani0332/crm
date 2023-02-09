<?php

namespace App\Repositories;

use App\Enums\PersonalQuoteTypes;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class BikeQuoteRepository extends BaseRepository
{
    public function model() {
        return PersonalQuote::class;
    }

    public function buildData($data)
    {
        return [
            "personalQuoteTypeId" => intval(PersonalQuoteTypes::BIKE->id()),
            "nationalityId" => $data['nationality_id'],
            "mobileNo"  => $data['mobile_no'],
            "email" => $data['email'],
            "firstName" => $data['first_name'],
            "lastName"  => $data['last_name'],
            "dob"   => $data['dob'],
            "bikeCompanyToInsure"   => $data['bike_company_to_insure'],
            "assetValue"    => $data['asset_value'],
            "currentlyInsuredWithId"    => $data['currently_insured_with_id'],
            "uaeLicenseHeldForId"   => $data['uae_license_held_for_id'],
            "yearOfManufacture" => $data['year_of_manufacture'],
            "lang"  => "EN",
            "device"    => "DESKTOP",
            "source"    => config('constants.SOURCE_NAME'),
            "referenceUrl"  => URL::current(),
        ];
    }

    /**
     * create new personal quote
     * @param $quoteTypeCode
     * @param $data
     * @return mixed
     */
    public function fetchCreate($data)
    {
        $data = $this->buildData($data);
        return Capi::request('/api/v1-save-personal-quote', 'post', $data);
    }

    /**
     * @param $uuid
     * @param $data
     * @return mixed
     */
    public function fetchUpdate($uuid, $data)
    {
        return DB::transaction(function() use($uuid, $data)
        {
            $quote = $this->byQuoteTypeId(PersonalQuoteTypes::BIKE->id())->where('uuid', $uuid)->firstOrFail();

            $quote->update(Arr::only($data, [
                'first_name', 'last_name', 'dob', 'nationality_id', 'email', 'mobile_no', 'asset_value', 'currently_insured_with_id'
            ]));

            return $quote;
        });
    }


    /**
     * get all dropdown options required for form
     * @return array
     */
    public function fetchGetFormOptions()
    {
        return [
            'nationalities' => NationalityRepository::withActive()->get(),
            'uaeLicenses' => UaeLicenseHeldRepository::withActive()->get(),
            'yearOfManufacture' => YearOfManufactureRepository::get(),
            'insuranceProviders' => InsuranceProviderRepository::select('id', 'text')->orderBy('text', 'asc')->get()
        ];
    }

    /**
     * @param $column
     * @param $value
     * @return mixed
     */
    public function fetchGetBy($column = 'id', $value) {

        return $this->byQuoteTypeId(PersonalQuoteTypes::BIKE->id())
            ->where($column, $value)
            ->with(['bikeQuote', 'advisor', 'nationality', 'quoteDetail.lostReason'])->firstOrFail();
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        return $this->byQuoteTypeCode(PersonalQuoteTypes::BIKE)->filter()->simplePaginate();
    }


}
