<?php

namespace App\Observers;

use App\Models\HomeQuote;
use App\Models\HomeQuoteRequestDetail;
use App\Traits\PersonalQuoteSyncTrait;

class HomeQuoteDetailObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the HomeQuoteRequestDetail "updated" event.
     */
    public function updated(HomeQuoteRequestDetail $homeQuoteDetail): void
    {
        $homeQuote = HomeQuote::find($homeQuoteDetail->home_quote_request_id);
        $this->syncQuote($homeQuote, $homeQuoteDetail->getDirty());
    }
}
