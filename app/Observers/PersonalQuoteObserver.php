<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Events\BikeQuoteAdvisorUpdated;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Models\PersonalQuote;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Support\Facades\Log;

class PersonalQuoteObserver
{
    use GenericQueriesAllLobs;

    public function updating(PersonalQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    public function updated(PersonalQuote $personalQuote): void
    {
        $dirty = $personalQuote->getDirty();
        if (
            $personalQuote->isDirty('quote_status_id') &&
            $personalQuote->quote_status_id === QuoteStatusEnum::TransactionApproved &&
            checkPersonalQuotes($personalQuote->quoteType?->code)
        ) {
            PersonalQuote::withoutEvents(function () use ($personalQuote) {
                $personalQuote->update(['transaction_approved_at' => now()]);
            });
        }

        // Bike Case in Lead Allocation Process- Bike Quote Advisor Change
        if (
            $personalQuote->quote_type_id === 6
        ) {
            $changes = [];

            foreach ($personalQuote->getDirty() as $attribute => $value) {
                if ($personalQuote->isDirty($attribute)) {
                    $changes[$attribute] = [
                        'old' => $personalQuote->getOriginal($attribute),
                        'new' => $value,
                    ];
                }
            }

            try {
                // handle the bikeQuote advisor_id change event in lead allocation
                if (
                    $personalQuote->isDirty('advisor_id') &&
                    $personalQuote->advisor_id !== null &&
                    $personalQuote->advisor_id !== 0
                ) {
                    $oldAdvisorId = $changes['advisor_id']['old'];
                    event(new BikeQuoteAdvisorUpdated($personalQuote, $oldAdvisorId));
                }
            } catch (Exception $e) {
                Log::error('PersonalQuoteObserver Error: '.$e->getMessage());
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            in_array($personalQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked]) &&
            in_array($personalQuote->quote_type_id, [QuoteTypeId::Pet, QuoteTypeId::Bike, QuoteTypeId::Cycle, QuoteTypeId::Yacht, QuoteTypeId::Jetski])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => $personalQuote->quote_type_id, 'quoteUID' => $personalQuote->uuid]);
            MAWelcomeJob::dispatch(
                $personalQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }

        if (isset($dirty['quote_status_id']) && $this->removeStaleFromLead($personalQuote->quote_status_id)
            && in_array($personalQuote->quote_type_id, [QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Yacht])) {
            PersonalQuote::withoutEvents(function () use ($personalQuote) {
                $personalQuote->update(['stale_at' => null]);
            });
        }
    }
}
