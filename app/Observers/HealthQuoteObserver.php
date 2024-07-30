<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Jobs\MAWelcomeJob;
use App\Models\HealthQuote;
use App\Services\HealthQuoteService;
use App\Traits\PersonalQuoteSyncTrait;
use App\Models\ApplicationStorage;

class HealthQuoteObserver
{
    use PersonalQuoteSyncTrait;

    /**
     * Handle the HealthQuote "updated" event.
     */
    public function updated(HealthQuote $healthQuote): void
    {
        $dirty = $healthQuote->getDirty();
        if (
            $healthQuote->isDirty('quote_status_id') &&
            $healthQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            MAWelcomeJob::dispatchIf(
                isMyAlfredCampaignEnabled(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN)) && $healthQuote->customer,
                $healthQuote->customer?->first_name,
                $healthQuote->customer?->last_name,
                $healthQuote->customer?->email,
                $healthQuote->customer?->mobile_no,
                'CUSTOMER_UPDATE',
                'customer-update-myalfred-we'
            );
            HealthQuote::withoutEvents(function () use ($healthQuote) {
                $healthQuote->update(['transaction_approved_at' => now()]);
            });

            $ecommerceSource = ApplicationStorage::where('key_name', ApplicationStorageEnums::LEAD_SOURCE_ECOMMERCE)->value('value');
            if ($healthQuote->source === LeadSourceEnum::IMCRM || strpos($healthQuote->source, $ecommerceSource) !== false) {
                app(HealthQuoteService::class)->assignRenewalBatch($healthQuote->id);
            }
            $dirty = [...$dirty, 'transaction_approved_at' => $healthQuote->transaction_approved_at];
        }

        $this->syncQuote($healthQuote, $dirty);
    }
}
