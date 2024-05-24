<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\PetQuote;
use App\Traits\PersonalQuoteSyncTrait;

class PetQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the PetQuote "updated" event.
     */
    public function updated(PetQuote $petQuote): void
    {
        $dirty = $petQuote->getDirty();
        if (
            $petQuote->isDirty('quote_status_id') &&
            $petQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            PetQuote::withoutEvents(function () use ($petQuote) {
                $petQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $petQuote->transaction_approved_at];
        }

        $this->syncQuote($petQuote, $dirty);
    }
}
