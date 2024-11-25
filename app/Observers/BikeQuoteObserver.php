<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\BikeQuote;

class BikeQuoteObserver
{
    /**
     * Handle the BikeQuote "updated" event.
     */
    public function updated(BikeQuote $bikeQuote): void
    {
        $dirty = $bikeQuote->getDirty();
        if (
            $bikeQuote->isDirty('quote_status_id') &&
            $bikeQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            BikeQuote::withoutEvents(function () use ($bikeQuote) {
                $bikeQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $bikeQuote->transaction_approved_at];
        }
    }
}
