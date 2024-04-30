<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Events\CarQuoteAdvisorUpdated;
use App\Models\CarQuote;
use App\Traits\PersonalQuoteSyncTrait;

class CarQuoteObserver
{
    use PersonalQuoteSyncTrait;

    public function updated(CarQuote $lead)
    {
        $changes = [];

        foreach ($lead->getDirty() as $attribute => $value) {
            if ($lead->isDirty($attribute)) {
                $changes[$attribute] = [
                    'old' => $lead->getOriginal($attribute),
                    'new' => $value,
                ];
            }
        }

        if ($lead->isDirty('advisor_id')) {
            $oldAdvisorId = $changes['advisor_id']['old'];
            event(new CarQuoteAdvisorUpdated($lead, $oldAdvisorId));
        }

        $dirty = $lead->getDirty();
        if ($lead->isDirty('quote_status_id') && $lead->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            CarQuote::withoutEvents(function () use ($lead) {
                $lead->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $lead->transaction_approved_at];
        }

        $this->syncQuote($lead, $dirty);
    }
}
