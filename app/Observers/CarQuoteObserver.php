<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Events\CarQuoteAdvisorUpdated;
use App\Jobs\MAWelcomeJob;
use App\Models\CarQuote;
use App\Repositories\PaymentRepository;
use App\Traits\PersonalQuoteSyncTrait;

class CarQuoteObserver
{
    use PersonalQuoteSyncTrait;

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
            MAWelcomeJob::dispatchIf(
                isMyAlfredCampaignEnabled(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN)) && $lead->customer,
                $lead->customer?->first_name,
                $lead->customer?->last_name,
                $lead->customer?->email,
                $lead->customer?->mobile_no,
                'CUSTOMER_UPDATE',
                'customer-update-myalfred-we'
            );

            CarQuote::withoutEvents(function () use ($lead) {
                $lead->update([
                    'transaction_approved_at' => now(),
                    'quote_status_date' => now(),
                ]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $lead->transaction_approved_at];
        }

        $this->syncQuote($lead, $dirty);

        if (isset($dirty['quote_status_id']) && $lead->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            $this->syncLeadEntries($lead->uuid);
        }

        if (
            isset($dirty['quote_status_id']) &&
            $lead->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            $payment = $lead->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($payment, QuoteTypes::CAR->value);

        }
    }
}
