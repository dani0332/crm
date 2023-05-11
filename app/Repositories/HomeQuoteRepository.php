<?php

namespace App\Repositories;

use App\Models\HomeQuote;

class HomeQuoteRepository extends BaseRepository
{
    public function model()
    {
        return HomeQuote::class;
    }

    public function fetchgetDuplicateEntityByCode($code)
    {
        return $this->where('parent_duplicate_quote_id', $code)->first();
    }

}
