<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Jobs\CourtesyEmailJob;
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

        if (
            $yachtQuote->isDirty('quote_status_id') &&
            in_array($yachtQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Yacht, 'quoteUID' => $yachtQuote->uuid]);
        }
    }
}
