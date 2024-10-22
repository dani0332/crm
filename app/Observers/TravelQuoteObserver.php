<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Events\TravelQuoteAdvisorUpdated;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Models\TravelQuote;
use App\Repositories\PaymentRepository;
use App\Traits\PersonalQuoteSyncTrait;

class TravelQuoteObserver
{
    use PersonalQuoteSyncTrait;

    public function updating(TravelQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    /**
     * Handle the TravelQuote "updated" event.
     */
    public function updated(TravelQuote $travelQuote): void
    {
        $dirty = $travelQuote->getDirty();
        $changes = [];

        foreach ($dirty as $attribute => $value) {
            if ($travelQuote->isDirty($attribute)) {
                $changes[$attribute] = [
                    'old' => $travelQuote->getOriginal($attribute),
                    'new' => $value,
                ];
            }
        }

        if ($travelQuote->isDirty('advisor_id')) {
            $travelQuote->markLeadAllocationPassed();
            $oldAdvisorId = $changes['advisor_id']['old'];
            TravelQuoteAdvisorUpdated::dispatch($travelQuote, $oldAdvisorId);
        }

        if (
            $travelQuote->isDirty('quote_status_id') &&
            $travelQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            TravelQuote::withoutEvents(function () use ($travelQuote) {
                $travelQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $travelQuote->transaction_approved_at];
        }

        $this->syncQuote($travelQuote, $dirty);

        if (isset($dirty['quote_status_id']) && $travelQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            $this->updatePersonalQuote($travelQuote->uuid, QuoteTypeId::Travel, $dirty);
        }

        if (
            $travelQuote->isDirty('quote_status_id') &&
            in_array($travelQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Travel, 'quoteUID' => $travelQuote->uuid]);
            MAWelcomeJob::dispatch(
                $travelQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }

        if (
            isset($dirty['quote_status_id']) &&
            $travelQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            $payment = $travelQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($payment, QuoteTypes::TRAVEL->value);

        }
    }
}
