<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Models\ApplicationStorage;
use App\Models\HealthQuote;
use App\Services\HealthQuoteService;
use App\Traits\PersonalQuoteSyncTrait;

class HealthQuoteObserver
{
    use PersonalQuoteSyncTrait;

    public function updating(HealthQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

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

        if (isset($dirty['quote_status_id']) && $healthQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            $this->syncLeadEntries($healthQuote->uuid);
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Health, 'quoteUID' => $healthQuote->uuid]);
        }
    }
}
