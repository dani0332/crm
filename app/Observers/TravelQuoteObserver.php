<?php

namespace App\Observers;

use App\Models\TravelQuote;
use App\Traits\PersonalQuoteSyncTrait;

class TravelQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the TravelQuote "updated" event.
     */
    public function updated(TravelQuote $travelQuote): void
    {
        $this->syncQuote($travelQuote, $travelQuote->getDirty());
    }
}