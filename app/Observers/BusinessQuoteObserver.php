<?php

namespace App\Observers;

use App\Models\BusinessQuote;
use App\Traits\PersonalQuoteSyncTrait;

class BusinessQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the BusinessQuote "updated" event.
     */
    public function updated(BusinessQuote $businessQuote): void
    {
        $this->syncQuote($businessQuote, $businessQuote->getDirty());
    }
}
