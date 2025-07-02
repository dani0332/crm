<?php

namespace App\Repositories;

use App\Models\PersonalQuote;
use App\Traits\GenericQueriesAllLobs;

class SavingsQuoteRepository extends BaseRepository
{
    use GenericQueriesAllLobs;

    public function model()
    {
        return PersonalQuote::class;
    }

    public function fetchGetBy($column, $value)
    {
        return $this->where($column, $value)->first();
    }
}
