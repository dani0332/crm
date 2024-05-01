<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\HomeQuote;
use App\Traits\PersonalQuoteSyncTrait;

class HomeQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the HomeQuote "updated" event.
     */
    public function updated(HomeQuote $homeQuote): void
    {
        $dirty = $homeQuote->getDirty();
        if (
            $homeQuote->isDirty('quote_status_id') &&
            $homeQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            HomeQuote::withoutEvents(function () use ($homeQuote) {
                $homeQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $homeQuote->transaction_approved_at];
        }

        $this->syncQuote($homeQuote, $dirty);
    }
}
