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
        if ($lifeQuote->isDirty('quote_status_id') && $lifeQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            LifeQuote::withoutEvents(function () use ($lifeQuote) {
                $lifeQuote->update(['transaction_approved_at' => now()]);
            });
        }

        $this->syncQuote($lifeQuote, $lifeQuote->getDirty());
    }
}
