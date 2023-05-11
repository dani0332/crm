<?php

namespace App\Repositories;

use App\Models\BusinessQuote;

class BusinessQuoteRepository extends BaseRepository
{
    public function model()
    {
        return BusinessQuote::class;
    }

    public function fetchgetDuplicateEntityByCode($code)
    {
        return $this->where('parent_duplicate_quote_id', $code)->first();
    }

}
