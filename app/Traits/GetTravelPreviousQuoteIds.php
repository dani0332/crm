<?php

namespace App\Traits;

use App\Models\TravelQuote;

trait GetTravelPreviousQuoteIds
{
    public function travelPreviousQuoteIds()
    {
        $travelRenewalsIds = TravelQuote::whereNotNull('previous_quote_policy_number')->pluck('id');
        $travelQuoteIds = TravelQuote::whereIn('id', $travelRenewalsIds)->pluck('id');

        return $travelQuoteIds;
    }
}
