<?php

namespace App\Repositories;

use App\Enums\PersonalQuoteTypes;
use App\Models\Nationality;
use App\Models\PersonalQuote;
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
    public function fetchCreate($quoteTypeCode, $data)
    {
        //todo: send call to capi when API will be available
        $data['personal_quote_type_id'] = PersonalQuoteTypeRepository::getByCode($quoteTypeCode)->id;
        $data['uuid'] = Str::orderedUuid();
        return $this->create($data);
    }

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
