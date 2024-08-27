<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Events\TravelQuoteAdvisorUpdated;
use App\Jobs\MAWelcomeJob;
use App\Models\TravelQuote;
use App\Traits\PersonalQuoteSyncTrait;

class TravelQuoteObserver
{
    use PersonalQuoteSyncTrait;

    public function updating(TravelQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id')) {
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
            $oldAdvisorId = $changes['advisor_id']['old'];
            TravelQuoteAdvisorUpdated::dispatch($travelQuote, $oldAdvisorId);
        }

        if (
            $travelQuote->isDirty('quote_status_id') &&
            $travelQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            MAWelcomeJob::dispatchIf(
                isMyAlfredCampaignEnabled(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN)) && $travelQuote->customer,
                $travelQuote->customer?->first_name,
                $travelQuote->customer?->last_name,
                $travelQuote->customer?->email,
                $travelQuote->customer?->mobile_no,
                'CUSTOMER_UPDATE',
                'customer-update-myalfred-we'
            );
            TravelQuote::withoutEvents(function () use ($travelQuote) {
                $travelQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $travelQuote->transaction_approved_at];
        }

        $this->syncQuote($travelQuote, $dirty);
    }
}
