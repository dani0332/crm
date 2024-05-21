<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Jobs\MAWelcomeJob;
use App\Models\PersonalQuote;

class PersonalQuoteObserver
{
    public function updated(PersonalQuote $personalQuote): void
    {
        if (
            $personalQuote->isDirty('quote_status_id') &&
            $personalQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            MAWelcomeJob::dispatchIf(
                checkPersonalQuotes($personalQuote->quoteType?->code) && isMyAlfredCampaignEnabled(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN)) && $personalQuote->customer,
                $personalQuote->customer?->first_name,
                $personalQuote->customer?->last_name,
                $personalQuote->customer?->email,
                $personalQuote->customer?->mobile_no,
                'CUSTOMER_UPDATE',
                'customer-update-myalfred-we'
            );

            PersonalQuote::withoutEvents(function () use ($personalQuote) {
                $personalQuote->update(['transaction_approved_at' => now()]);
            });
        }
    }
}
