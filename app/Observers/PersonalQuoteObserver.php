<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Models\PersonalQuote;

class PersonalQuoteObserver
{
    public function updated(PersonalQuote $personalQuote): void
    {
        if (
            $personalQuote->isDirty('quote_status_id') &&
            $personalQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            PersonalQuote::withoutEvents(function () use ($personalQuote) {
                $personalQuote->update(['transaction_approved_at' => now()]);
            });
        }
    }
}
