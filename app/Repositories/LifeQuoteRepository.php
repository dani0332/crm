<?php

namespace App\Repositories;

use App\Models\LifeQuote;

class LifeQuoteRepository extends BaseRepository
{
    public function model()
    {
        return LifeQuote::class;
    }

    public function fetchgetDuplicateEntityByCode($code)
    {
        return $this->where('parent_duplicate_quote_id', $code)->first();
    }

}
