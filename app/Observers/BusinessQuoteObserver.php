<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Models\BusinessQuote;
use App\Traits\PersonalQuoteSyncTrait;

class BusinessQuoteObserver
{
    use PersonalQuoteSyncTrait;

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
                MAWelcomeJob::dispatchIf(
                    isMyAlfredCampaignEnabled(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN)) && $businessQuote->customer,
                    $businessQuote->customer?->first_name,
                    $businessQuote->customer?->last_name,
                    $businessQuote->customer?->email,
                    $businessQuote->customer?->mobile_no,
                    'CUSTOMER_UPDATE',
                    'customer-update-myalfred-we'
                );
                $businessQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $businessQuote->transaction_approved_at];
        }

        $this->syncQuote($businessQuote, $dirty);

        if (isset($dirty['quote_status_id']) && $businessQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            $this->syncLeadEntries($businessQuote->uuid);
        }

        if (
            $businessQuote->isDirty('quote_status_id') &&
            in_array($businessQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Business, 'quoteUID' => $businessQuote->uuid]);
        }
    }
}
