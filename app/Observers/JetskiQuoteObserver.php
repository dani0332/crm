<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Jobs\CourtesyEmailJob;
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

        if (
            $jetskiQuote->isDirty('quote_status_id') &&
            in_array($jetskiQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Jetski, 'quoteUID' => $jetskiQuote->uuid]);
        }
    }
}
