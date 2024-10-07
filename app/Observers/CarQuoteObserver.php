<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Events\CarQuoteAdvisorUpdated;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Models\CarQuote;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteSyncTrait;

class CarQuoteObserver
{
    use GenericQueriesAllLobs, PersonalQuoteSyncTrait;

    public function updating(CarQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    public function updated(CarQuote $lead)
    {
        $dirty = $lead->getDirty();
        $changes = [];

        foreach ($dirty as $attribute => $value) {
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

        if ($lead->isDirty('quote_status_id') && $lead->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            CarQuote::withoutEvents(function () use ($lead) {
                $lead->update([
                    'transaction_approved_at' => now(),
                    'quote_status_date' => now(),
                ]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $lead->transaction_approved_at];
        }

        if ($lead->isDirty('quote_status_id') && $this->markLeadStale($lead->quote_status_id)) {
            $lead->update(['stale_at' => null]);
        }

        $this->syncQuote($lead, $dirty);

        if (isset($dirty['quote_status_id']) && $lead->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            $this->syncLeadEntries($lead->uuid);
        }

        if (
            $lead->isDirty('quote_status_id') &&
            in_array($lead->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Car, 'quoteUID' => $lead->uuid]);
            MAWelcomeJob::dispatch(
                $lead->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }

    }
}
