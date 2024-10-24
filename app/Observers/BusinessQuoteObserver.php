<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Jobs\MAWelcomeJob;
use App\Models\BusinessQuote;
use App\Repositories\PaymentRepository;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteSyncTrait;

class BusinessQuoteObserver
{
    use GenericQueriesAllLobs, PersonalQuoteSyncTrait;

    public function updating(BusinessQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    /**
     * Handle the BusinessQuote "updated" event.
     */
    public function updated(BusinessQuote $businessQuote): void
    {
        $dirty = $businessQuote->getDirty();
        if (
            $businessQuote->isDirty('quote_status_id') &&
            $businessQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            BusinessQuote::withoutEvents(function () use ($businessQuote) {
                $businessQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $businessQuote->transaction_approved_at];
        }

        if (isset($dirty['quote_status_id']) && $this->removeStaleFromLead($businessQuote->quote_status_id)) {
            BusinessQuote::withoutEvents(function () use ($businessQuote) {
                $businessQuote->update(['stale_at' => null]);
            });
            $dirty = [...$dirty, 'stale_at' => $businessQuote->stale_at];
        }

        $this->syncQuote($businessQuote, $dirty);

        if (isset($dirty['quote_status_id']) && $businessQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            $this->updatePersonalQuote($businessQuote->uuid, QuoteTypeId::Business, $dirty);
        }

        if (
            isset($dirty['quote_status_id']) &&
            in_array($businessQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            MAWelcomeJob::dispatch(
                $businessQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }

        if (
            isset($dirty['quote_status_id']) &&
            $businessQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            $payment = $businessQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($businessQuote, $payment, QuoteTypes::BUSINESS->value);

        }
    }
}
