<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\LifeQuote;
use App\Traits\PersonalQuoteSyncTrait;

class LifeQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the LifeQuote "updated" event.
     */
    public function updated(LifeQuote $lifeQuote): void
    {
        $dirty = $lifeQuote->getDirty();
        if (
            $lifeQuote->isDirty('quote_status_id') &&
            $lifeQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            LifeQuote::withoutEvents(function () use ($lifeQuote) {
                $lifeQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $lifeQuote->transaction_approved_at];
        }

        $this->syncQuote($lifeQuote, $dirty);
    }
}
