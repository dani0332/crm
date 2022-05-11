<?php
namespace App\Traits;

use App\Models\TravelQuote;
use App\Models\User;

trait GetTravelPreviousQuoteIds {

    function travelPreviousQuoteIds () {

        $travelRenewalsIds = TravelQuote::whereNotNull('previous_quote_id')->pluck('id');
        $travelQuoteIds = TravelQuote::whereIn('id', $travelRenewalsIds)->pluck('id');

        return $travelQuoteIds;
    }
}
