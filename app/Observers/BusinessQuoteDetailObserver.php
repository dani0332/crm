<?php

namespace App\Observers;

use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Traits\PersonalQuoteSyncTrait;

class BusinessQuoteDetailObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the BusinessQuoteRequestDetail "updated" event.
     */
    public function updated(BusinessQuoteRequestDetail $businessQuoteDetail): void
    {
        $businessQuote = BusinessQuote::find($businessQuoteDetail->business_quote_request_id);
        $this->syncQuote($businessQuote, $businessQuoteDetail->getDirty());
    }
}
