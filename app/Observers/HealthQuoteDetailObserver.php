<?php

namespace App\Observers;

use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Traits\PersonalQuoteSyncTrait;

class HealthQuoteDetailObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the HealthQuoteRequestDetail "updated" event.
     */
    public function updated(HealthQuoteRequestDetail $healthQuoteDetail): void
    {
        $healthQuote = HealthQuote::find($healthQuoteDetail->health_quote_request_id);
        $this->syncQuote($healthQuote, $healthQuoteDetail->getDirty());
    }
}
