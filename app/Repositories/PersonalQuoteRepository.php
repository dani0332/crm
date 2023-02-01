<?php

namespace App\Repositories;

use App\Models\PersonalQuote;
use Illuminate\Support\Str;

class PersonalQuoteRepository extends BaseRepository
{
    public function model() {
        return PersonalQuote::class;
    }

    public function fetchGetData($quoteTypeCode)
    {
        $query = $this->query();

        //todo: handle filters later by trait
        if(!empty(request()->first_name)) $query->where('first_name', request()->first_name);
        if(!empty(request()->last_name)) $query->where('last_name', request()->last_name);
        if(!empty(request()->uuid))  $query->where('uuid', request()->uuid);
        if(!empty(request()->email)) $query->where('email', request()->email);
        if(!empty(request()->mobile_no)) $query->where('mobile_no', request()->mobile_no);

        return $query->byQuoteTypeCode($quoteTypeCode)
            ->simplePaginate(10)
            ->withQueryString();
    }


}
