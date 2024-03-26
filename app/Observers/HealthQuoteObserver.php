<?php

namespace App\Observers;

use App\Models\HealthQuote;
use App\Traits\PersonalQuoteSyncTrait;

class HealthQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the HealthQuote "updated" event.
     */
    public function updated(HealthQuote $healthQuote): void
    {
        $this->syncQuote($healthQuote, $healthQuote->getDirty());
    }
}
