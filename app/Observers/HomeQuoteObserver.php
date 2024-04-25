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
        if ($homeQuote->isDirty('quote_status_id') && $homeQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            HomeQuote::withoutEvents(function () use ($homeQuote) {
                $homeQuote->update(['transaction_approved_at' => now()]);
            });
        }

        $this->syncQuote($homeQuote, $homeQuote->getDirty());
    }
}
