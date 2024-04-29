<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\HealthQuote;
use App\Traits\PersonalQuoteSyncTrait;

class HealthQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the HealthQuote "updated" event.
     */
    public function updated(HealthQuote $healthQuote): void
    {
        $dirty = $healthQuote->getDirty();
        if (
            $healthQuote->isDirty('quote_status_id') &&
            $healthQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            HealthQuote::withoutEvents(function () use ($healthQuote) {
                $healthQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $healthQuote->transaction_approved_at];
        }

        $this->syncQuote($healthQuote, $dirty);
    }
}
