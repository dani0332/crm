<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\BikeQuote;
use App\Traits\PersonalQuoteSyncTrait;

class BikeQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the BikeQuote "updated" event.
     */
    public function updated(BikeQuote $bikeQuote): void
    {
        if ($bikeQuote->isDirty('quote_status_id') && $bikeQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            BikeQuote::withoutEvents(function () use ($bikeQuote) {
                $bikeQuote->update(['transaction_approved_at' => now()]);
            });
        }

        $this->syncQuote($bikeQuote, $bikeQuote->getDirty());
    }
}
