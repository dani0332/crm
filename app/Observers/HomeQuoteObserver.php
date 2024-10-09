<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Models\HomeQuote;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteSyncTrait;

class HomeQuoteObserver
{
    use GenericQueriesAllLobs, PersonalQuoteSyncTrait;

    public function updating(HomeQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    /**
     * Handle the HomeQuote "updated" event.
     */
    public function updated(HomeQuote $homeQuote): void
    {
        $dirty = $homeQuote->getDirty();
        if (
            $homeQuote->isDirty('quote_status_id') &&
            $homeQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            HomeQuote::withoutEvents(function () use ($homeQuote) {
                $homeQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $homeQuote->transaction_approved_at];
        }

        if (isset($dirty['quote_status_id']) && $this->removeStaleFromLead($homeQuote->quote_status_id)) {
            $homeQuote->update(['stale_at' => null]);
        }

        $this->syncQuote($homeQuote, $dirty);

        if (isset($dirty['quote_status_id']) && $homeQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            $this->syncLeadEntries($homeQuote->uuid);
        }

        if (
            $homeQuote->isDirty('quote_status_id') &&
            in_array($homeQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Home, 'quoteUID' => $homeQuote->uuid]);
            MAWelcomeJob::dispatch(
                $homeQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }
    }
}
