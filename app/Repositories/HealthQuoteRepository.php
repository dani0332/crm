<?php

namespace App\Repositories;

use App\Models\HealthQuote;

class HealthQuoteRepository extends BaseRepository
{
    public function model()
    {
        return HealthQuote::class;
    }

    public function fetchgetDuplicateEntityByCode($code)
    {
        return $this->where('parent_duplicate_quote_id', $code)->first();
    }

}
