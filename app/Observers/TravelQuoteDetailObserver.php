<?php

namespace App\Observers;

use App\Models\TravelQuote;
use App\Models\TravelQuoteRequestDetail;
use App\Traits\PersonalQuoteSyncTrait;

class TravelQuoteDetailObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the TravelQuoteRequestDetail "updated" event.
     */
    public function updated(TravelQuoteRequestDetail $travelQuoteDetail): void
    {
        $travelQuote = TravelQuote::find($travelQuoteDetail->travel_quote_request_id);
        $this->syncQuote($travelQuote, $travelQuoteDetail->getDirty());
    }
}
