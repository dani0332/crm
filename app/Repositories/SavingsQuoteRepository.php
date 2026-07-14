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
        $quote = $this->where($column, $value)->with('previousQuote:id,uuid,code')->first();

        return $quote;
    }
}
