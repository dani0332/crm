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
        return $query->byQuoteTypeCode($quoteTypeCode)
            ->simplePaginate(10)
            ->withQueryString();
    }


}
