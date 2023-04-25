<?php

namespace App\Repositories;

use App\Models\LifeQuote;

class LifeQuoteRepository extends BaseRepository
{
    public function model()
    {
        return LifeQuote::class;
    }

    public function fetchGetData()
    {
        return $this->filter()->where('quote_status_id', '!=', 9)->with(['advisor', 'quoteStatus', 'nationality'])->orderBy('created_at', 'desc')->simplePaginate();
    }
}
