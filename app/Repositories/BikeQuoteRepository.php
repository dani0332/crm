<?php

namespace App\Repositories;

use App\Enums\PersonalQuoteTypes;
use App\Models\PersonalQuote;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BikeQuoteRepository extends BaseRepository
{
    public function model() {
        return PersonalQuote::class;
    }

    /**
     * create new personal quote
     * @param $quoteTypeCode
     * @param $data
     * @return mixed
     */
    public function fetchCreate($data)
    {
        //todo: send call to capi when API will be available
        $data['personal_quote_type_id'] = PersonalQuoteTypeRepository::getByCode(PersonalQuoteTypes::BIKE)->id;
        $data['uuid'] = Str::orderedUuid();
        return  $this->create(Arr::only($data, ['first_name', 'last_name', 'email', 'mobile_no', 'personal_quote_type_id', 'uuid']));
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
        return $this->where($column, $value)->with('bikeQuote')->first();
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        return $this->byQuoteTypeCode(PersonalQuoteTypes::BIKE)->filter()->simplePaginate();
    }


}
