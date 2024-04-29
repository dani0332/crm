<?php

namespace App\Observers;

use App\Models\HealthQuote;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Services\HealthQuoteService;
use App\Traits\PersonalQuoteSyncTrait;

class HealthQuoteObserver
{
    use PersonalQuoteSyncTrait;

    protected $healthQuoteService;

    public function __construct()
    {
        $this->healthQuoteService = app(HealthQuoteService::class);
    }

    /**
     * Handle the HealthQuote "updated" event.
     */
    public function updated(HealthQuote $healthQuote): void
    {
        if ($healthQuote->isDirty('quote_status_id') && $healthQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            HealthQuote::withoutEvents(function () use ($healthQuote) {
                $healthQuote->update(['transaction_approved_at' => now()]);
            });

            if ($healthQuote->source === LeadSourceEnum::IMCRM)
            {
                $this->healthQuoteService->assignRenewalBatch($healthQuote);
            }

        }

        $this->syncQuote($healthQuote, $healthQuote->getDirty());
    }
}
