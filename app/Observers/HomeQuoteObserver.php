<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Jobs\MAWelcomeJob;
use App\Models\HomeQuote;
use App\Traits\PersonalQuoteSyncTrait;

class HomeQuoteObserver
{
    use PersonalQuoteSyncTrait;

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
            MAWelcomeJob::dispatchIf(
                isMyAlfredCampaignEnabled(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN)) && $homeQuote->customer,
                $homeQuote->customer?->first_name,
                $homeQuote->customer?->last_name,
                $homeQuote->customer?->email,
                $homeQuote->customer?->mobile_no,
                'CUSTOMER_UPDATE',
                'customer-update-myalfred-we'
            );

            HomeQuote::withoutEvents(function () use ($homeQuote) {
                $homeQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $homeQuote->transaction_approved_at];
        }

        $this->syncQuote($homeQuote, $dirty);
    }
}
