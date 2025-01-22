<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Events\BikeQuoteAdvisorUpdated;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Jobs\SendHomeOCBIntroEmailJob;
use App\Models\PersonalQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\PaymentRepository;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class PersonalQuoteObserver
{
    use GenericQueriesAllLobs;

    public function updating(PersonalQuote $quote): void
    {
        info("PersonalQuoteObserver - Updating - Ref ID: {$quote->uuid} | Time: ".now());
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    /**
     * Handle the "updated" event.
     *
     * - Any changes that adds business logic should be enclosed in try-catch block or executed in queue.
     */
    public function updated(PersonalQuote $personalQuote): void
    {
        info("PersonalQuoteObserver - Updated - Ref ID: {$personalQuote->uuid} | Time: ".now());
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
                    $personalQuote->markLeadAllocationPassed();
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
            in_array($personalQuote->quote_type_id, [QuoteTypeId::Pet, QuoteTypeId::Bike, QuoteTypeId::Cycle, QuoteTypeId::Yacht, QuoteTypeId::Jetski, QuoteTypeId::Home])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => $personalQuote->quote_type_id, 'quoteUID' => $personalQuote->uuid]);
            MAWelcomeJob::dispatch(
                $personalQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );

            if ($personalQuote->quote_type_id === QuoteTypeId::Bike) {
                try {
                    EmbeddedProductRepository::capturePayment($personalQuote->id, QuoteTypes::getName($personalQuote->quote_type_id)->value);
                } catch (Exception $e) {
                    Log::error('PersonalQuoteObserver - capture embedded products failed', [
                        'error' => $e->getMessage(),
                        'uuid' => $personalQuote->uuid,
                    ]);
                }
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            $personalQuote->quote_type_id === QuoteTypeId::Bike &&
            $personalQuote->quote_status_id === QuoteStatusEnum::PolicyCancelled
        ) {
            try {
                EmbeddedProductRepository::cancelEmbeddedProducts($personalQuote->id, quoteTypeCode::Bike);
            } catch (Exception $e) {
                Log::error('PersonalQuoteObserver - cancel embedded products failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $personalQuote->uuid,
                ]);
            }
        }

        if (
            isset($dirty['quote_status_id']) && $this->removeStaleFromLead($personalQuote->quote_status_id)
            && in_array($personalQuote->quote_type_id, [QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Yacht, QuoteTypeId::Home])
        ) {
            PersonalQuote::withoutEvents(function () use ($personalQuote) {
                $personalQuote->update(['stale_at' => null]);
            });
        }

        if (
            isset($dirty['quote_status_id']) &&
            $personalQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            $payment = $personalQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($personalQuote, $payment, QuoteTypes::PERSONAL->value);
        }

        if ($this->isEligibleForHomeIntroEmail($personalQuote)) {

            info(self::class." - sending home intro email for quote: {$personalQuote->uuid} | Time: ".now());
            SendHomeOCBIntroEmailJob::dispatch($personalQuote->uuid)->delay(Carbon::now()->addMinutes(1));
            info(self::class.' - dispatched home intro email - Ref ID:'.$personalQuote->uuid.' | Time: '.now());
        }
    }

    private function isEligibleForHomeIntroEmail(PersonalQuote $personalQuote): bool
    {
        return $personalQuote->quote_type_id === 2 &&
            $personalQuote->isDirty('advisor_id') &&
            ! empty($personalQuote->advisor_id) &&
            $personalQuote->advisor_id !== 0;
    }

}
