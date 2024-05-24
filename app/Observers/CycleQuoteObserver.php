<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\CycleQuote;
use App\Traits\PersonalQuoteSyncTrait;

class CycleQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the CycleQuote "updated" event.
     */
    public function updated(CycleQuote $cycleQuote): void
    {
        $dirty = $cycleQuote->getDirty();
        if (
            $cycleQuote->isDirty('quote_status_id') &&
            $cycleQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            CycleQuote::withoutEvents(function () use ($cycleQuote) {
                $cycleQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $cycleQuote->transaction_approved_at];
        }

        $this->syncQuote($cycleQuote, $dirty);
    }
}
