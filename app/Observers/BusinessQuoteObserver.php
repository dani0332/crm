<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\BusinessQuote;
use App\Traits\PersonalQuoteSyncTrait;

class BusinessQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the BusinessQuote "updated" event.
     */
    public function updated(BusinessQuote $businessQuote): void
    {
        if ($businessQuote->isDirty('quote_status_id') && $businessQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            BusinessQuote::withoutEvents(function () use ($businessQuote) {
                $businessQuote->update(['transaction_approved_at' => now()]);
            });
        }

        $this->syncQuote($businessQuote, $businessQuote->getDirty());
    }
}
