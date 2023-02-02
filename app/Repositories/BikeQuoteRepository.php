<?php

namespace App\Repositories;

use App\Enums\PersonalQuoteTypes;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Models\UAELicenseHeldFor;
use App\Models\YearOfManufacture;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
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
        $query = $this->query();

        //todo: handle filters later by trait
        if(!empty(request()->first_name)) $query->where('first_name', request()->first_name);
        if(!empty(request()->last_name)) $query->where('last_name', request()->last_name);
        if(!empty(request()->uuid))  $query->where('uuid', request()->uuid);
        if(!empty(request()->email)) $query->where('email', request()->email);
        if(!empty(request()->mobile_no)) $query->where('mobile_no', request()->mobile_no);

        return $query->byQuoteTypeCode(PersonalQuoteTypes::BIKE)
            ->simplePaginate(10)
            ->withQueryString();
    }


}
