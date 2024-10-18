<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\Health\SendApplicationSubmittedEmailJob;
use App\Jobs\IntroEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Models\ApplicationStorage;
use App\Models\HealthQuote;
use App\Services\HealthQuoteService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteSyncTrait;

class HealthQuoteObserver
{
    use GenericQueriesAllLobs, PersonalQuoteSyncTrait;

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
            $healthQuote->isDirty('quote_status_id')
        ) {
            if ($healthQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
                HealthQuote::withoutEvents(function () use ($healthQuote) {
                    $healthQuote->update(['transaction_approved_at' => now()]);
                });

                $ecommerceSource = ApplicationStorage::where('key_name', ApplicationStorageEnums::LEAD_SOURCE_ECOMMERCE)->value('value');
                if ($healthQuote->source === LeadSourceEnum::IMCRM || strpos($healthQuote->source, $ecommerceSource) !== false) {
                    app(HealthQuoteService::class)->assignRenewalBatch($healthQuote->id);
                }
                $dirty = [...$dirty, 'transaction_approved_at' => $healthQuote->transaction_approved_at];
            }

            if ($healthQuote->quote_status_id === QuoteStatusEnum::ApplicationSubmitted) {
                SendApplicationSubmittedEmailJob::dispatch($healthQuote);
            }
        }

        if (isset($dirty['quote_status_id']) && $this->removeStaleFromLead($healthQuote->quote_status_id)) {
            HealthQuote::withoutEvents(function () use ($healthQuote) {
                $healthQuote->update(['stale_at' => null]);
            });
            $dirty = [...$dirty, 'stale_at' => $healthQuote->stale_at];
        }

        if ($healthQuote->isDirty('advisor_id')) {
            $healthQuote->markLeadAllocationPassed();
        }

        $this->syncQuote($healthQuote, $dirty);

        if (isset($dirty['quote_status_id']) && $healthQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            $this->updatePersonalQuote($healthQuote->uuid, QuoteTypeId::Health, $dirty);
        }

        if (isset($dirty['quote_status_id']) && $healthQuote->quote_status_id === QuoteStatusEnum::Qualified && $healthQuote->advisor_id) {
            info("Quote status changed to {$healthQuote->quote_status_id} | Ref-ID: {$healthQuote->uuid} | Time: ".now());
            IntroEmailJob::dispatch(quoteTypeCode::Health, 'Capi', $healthQuote->uuid, 'send-rm-intro-email', null, false);
        }
        if (
            isset($dirty['quote_status_id']) &&
            in_array($healthQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Health, 'quoteUID' => $healthQuote->uuid]);
            MAWelcomeJob::dispatch(
                $healthQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }
    }
}
