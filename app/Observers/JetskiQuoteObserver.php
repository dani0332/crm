<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\JetskiQuote;

class JetskiQuoteObserver
{
    /**
     * Handle the JetskiQuote "updated" event.
     */
    public function updated(JetskiQuote $jetskiQuote): void
    {
        $dirty = $jetskiQuote->getDirty();
        if (
            $jetskiQuote->isDirty('quote_status_id') &&
            $jetskiQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            JetskiQuote::withoutEvents(function () use ($jetskiQuote) {
                $jetskiQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $jetskiQuote->transaction_approved_at];
        }
    }
}
