<?php

namespace App\Observers;

use App\Models\LifeQuote;
use App\Models\LifeQuoteRequestDetail;
use App\Traits\PersonalQuoteSyncTrait;

class LifeQuoteDetailObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the LifeQuoteRequestDetail "updated" event.
     */
    public function updated(LifeQuoteRequestDetail $lifeQuoteDetail): void
    {
        $lifeQuote = LifeQuote::find($lifeQuoteDetail->life_quote_request_id);
        $this->syncQuote($lifeQuote, $lifeQuoteDetail->getDirty());
    }
}
