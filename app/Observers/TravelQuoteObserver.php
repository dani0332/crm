<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
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
        if ($travelQuote->isDirty('quote_status_id') && $travelQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            TravelQuote::withoutEvents(function () use ($travelQuote) {
                $travelQuote->update(['transaction_approved_at' => now()]);
            });
        }

        $this->syncQuote($travelQuote, $travelQuote->getDirty());
    }
}
