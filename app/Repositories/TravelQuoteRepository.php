<?php

namespace App\Repositories;

use App\Models\TravelQuote;

class TravelQuoteRepository extends BaseRepository
{
    public function model()
    {
        return TravelQuote::class;
    }

    public function fetchgetDuplicateEntityByCode($code)
    {
        return $this->where('parent_duplicate_quote_id', $code)->first();
    }

}
