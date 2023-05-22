<?php

namespace App\Repositories;

use App\Models\CarQuote;
use App\Models\PersonalQuote;

class CarQuoteRepository extends BaseRepository
{
    public function model()
    {
        return CarQuote::class;
    }

    public function fetchGetLostQuotes($quoteStatusId)
    {
        $query =  $this->where('quote_status_id', $quoteStatusId)
            ->filter();

        if(!empty(request()->approval_status)) {
            $query->with('carLostQuoteLog')
            ->whereHas('carLostQuoteLog' , function($q) {
                $q->where('status', request()->approval_status);
            });
        }

        return $query->orderBy('created_at', 'desc')->simplePaginate();
    }

}
