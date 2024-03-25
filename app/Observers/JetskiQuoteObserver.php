<?php

namespace App\Observers;

use App\Models\JetskiQuote;
use App\Traits\PersonalQuoteSyncTrait;

class JetskiQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the JetskiQuote "updated" event.
     */
    public function updated(JetskiQuote $jetskiQuote): void
    {
        $this->syncQuote($jetskiQuote, $jetskiQuote->getDirty());
    }
}