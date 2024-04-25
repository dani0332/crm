<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\YachtQuote;
use App\Traits\PersonalQuoteSyncTrait;

class YachtQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the YachtQuote "updated" event.
     */
    public function updated(YachtQuote $yachtQuote): void
    {
        if ($yachtQuote->isDirty('quote_status_id') && $yachtQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            YachtQuote::withoutEvents(function () use ($yachtQuote) {
                $yachtQuote->update(['transaction_approved_at' => now()]);
            });
        }

        $this->syncQuote($yachtQuote, $yachtQuote->getDirty());
    }
}
