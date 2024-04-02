<?php

namespace App\Observers;

use App\Models\LifeQuote;
use App\Traits\PersonalQuoteSyncTrait;

class LifeQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the LifeQuote "updated" event.
     */
    public function updated(LifeQuote $lifeQuote): void
    {
        $this->syncQuote($lifeQuote, $lifeQuote->getDirty());
    }
}
