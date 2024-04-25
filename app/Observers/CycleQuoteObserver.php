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
        if ($cycleQuote->isDirty('quote_status_id') && $cycleQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            CycleQuote::withoutEvents(function () use ($cycleQuote) {
                $cycleQuote->update(['transaction_approved_at' => now()]);
            });
        }

        $this->syncQuote($cycleQuote, $cycleQuote->getDirty());
    }
}
