<?php

namespace App\Observers;

use App\Models\HomeQuote;
use App\Traits\PersonalQuoteSyncTrait;

class HomeQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the HomeQuote "updated" event.
     */
    public function updated(HomeQuote $homeQuote): void
    {
        $this->syncQuote($homeQuote, $homeQuote->getDirty());
    }
}
