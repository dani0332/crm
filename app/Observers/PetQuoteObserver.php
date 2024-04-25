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
        if ($petQuote->isDirty('quote_status_id') && $petQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            PetQuote::withoutEvents(function () use ($petQuote) {
                $petQuote->update(['transaction_approved_at' => now()]);
            });
        }

        $this->syncQuote($petQuote, $petQuote->getDirty());
    }
}
