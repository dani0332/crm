<?php

namespace App\Observers;

use App\Models\BusinessQuote;
use App\Traits\PersonalQuoteSyncTrait;
use App\Models\BusinessQuoteRequestDetail;

class BusinessQuoteDetailObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the BusinessQuoteRequestDetail "updated" event.
     */
    public function updated(BusinessQuoteRequestDetail $businessQuoteDetail): void
    {
        $businessQuote = BusinessQuote::find($businessQuoteDetail->business_quote_request_id);
        $this->syncQuoteDetail($businessQuote, $businessQuoteDetail->getDirty());
    }
}
