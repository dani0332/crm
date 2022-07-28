<?php

namespace App\Traits;

use App\Models\TravelQuote;

trait GetTravelPreviousQuoteIds
{
    public function travelPreviousQuoteIds()
    {
        $travelRenewalsIds = TravelQuote::whereNotNull('previous_quote_id')->pluck('id');
        $travelQuoteIds = TravelQuote::whereIn('id', $travelRenewalsIds)->pluck('id');

        return $travelQuoteIds;
    }
}
