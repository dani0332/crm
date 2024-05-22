<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\YachtQuote;

class YachtQuoteObserver
{
    /**
     * Handle the YachtQuote "updated" event.
     */
    public function updated(YachtQuote $yachtQuote): void
    {
        $dirty = $yachtQuote->getDirty();
        if (
            $yachtQuote->isDirty('quote_status_id') &&
            $yachtQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            YachtQuote::withoutEvents(function () use ($yachtQuote) {
                $yachtQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $yachtQuote->transaction_approved_at];
        }
    }
}
