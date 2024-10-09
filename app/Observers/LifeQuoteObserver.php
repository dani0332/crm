<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Jobs\MAWelcomeJob;
use App\Models\LifeQuote;
use App\Repositories\PaymentRepository;
use App\Traits\PersonalQuoteSyncTrait;

class LifeQuoteObserver
{
    use PersonalQuoteSyncTrait;

    public function updating(LifeQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    /**
     * Handle the LifeQuote "updated" event.
     */
    public function updated(LifeQuote $lifeQuote): void
    {
        $dirty = $lifeQuote->getDirty();
        if (
            $lifeQuote->isDirty('quote_status_id') &&
            $lifeQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            MAWelcomeJob::dispatchIf(
                isMyAlfredCampaignEnabled(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN)) && $lifeQuote->customer,
                $lifeQuote->customer?->first_name,
                $lifeQuote->customer?->last_name,
                $lifeQuote->customer?->email,
                $lifeQuote->customer?->mobile_no,
                'CUSTOMER_UPDATE',
                'customer-update-myalfred-we'
            );

            LifeQuote::withoutEvents(function () use ($lifeQuote) {
                $lifeQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $lifeQuote->transaction_approved_at];
        }

        $this->syncQuote($lifeQuote, $dirty);

        if (isset($dirty['quote_status_id']) && $lifeQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            $this->syncLeadEntries($lifeQuote->uuid);
        }

        if (
            isset($dirty['quote_status_id']) &&
            $lifeQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            $payment = $lifeQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($payment, QuoteTypes::LIFE->value);

        }
    }
}
