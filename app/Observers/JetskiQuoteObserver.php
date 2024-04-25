<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\JetskiQuote;
use App\Traits\PersonalQuoteSyncTrait;

class JetskiQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the JetskiQuote "updated" event.
     */
    public function updated(JetskiQuote $jetskiQuote): void
    {
        if ($jetskiQuote->isDirty('quote_status_id') && $jetskiQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            JetskiQuote::withoutEvents(function () use ($jetskiQuote) {
                $jetskiQuote->update(['transaction_approved_at' => now()]);
            });
        }

        $this->syncQuote($jetskiQuote, $jetskiQuote->getDirty());
    }
}
