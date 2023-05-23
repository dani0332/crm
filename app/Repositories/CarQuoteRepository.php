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

    /**
     * @param $quoteStatusId (CarLost/Uncontactable)
     * @return mixed
     */
    public function fetchGetLostQuotes($quoteStatusId)
    {
        $query =  $this->where('quote_status_id', $quoteStatusId)->with('carLostQuoteLog')
            ->filter();

        if(!empty(request()->approval_status)) {
            $query->whereHas('carLostQuoteLog' , function($q) {
                $q->where('status', request()->approval_status);
            });
        }

        return $query->orderBy('created_at', 'desc')->simplePaginate();
    }

}
