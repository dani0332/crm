<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Events\CarQuoteAdvisorUpdated;
use App\Events\LeadStatusUpdated;
use App\Events\PrivateClientUpdatedEvent;
use App\Jobs\Audit\LogAllocation;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Models\CarQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\PaymentRepository;
use App\Services\SendEmailCustomerService;
use App\Traits\PersonalQuoteSyncTrait;
use Exception;
use Illuminate\Support\Facades\Log;

class CarQuoteObserver
{
    use PersonalQuoteSyncTrait;

    public function updating(CarQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    /**
     * Handle the "updated" event.
     *
     * - Any changes that adds business logic should be enclosed in try-catch block or executed in queue.
     */
    public function updated(CarQuote $lead)
    {
        $dirty = $lead->getDirty();
        $changes = [];

        foreach ($dirty as $attribute => $value) {
            $changes[$attribute] = [
                'old' => $lead->getOriginal($attribute),
                'new' => $value,
            ];
        }

        if (isset($dirty['advisor_id'])) {
            try {
                $lead->markLeadAllocationPassed();
                $oldAdvisorId = $changes['advisor_id']['old'];

                LogAllocation::dispatch($lead, QuoteTypes::CAR);

                event(new CarQuoteAdvisorUpdated($lead, $oldAdvisorId));
            } catch (Exception $e) {
                Log::error('CarQuoteObserver - handle car update advisor failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $lead->uuid,
                ]);
            }
        }

        if (isset($dirty['ai_advisor_id']) && ! empty($lead->ai_advisor_id)) {
            app(SendEmailCustomerService::class)->sendCarIntroEmailWithAIAdvisor($lead);
        }

        if (isset($dirty['quote_status_id'])) {
            if ($lead->quote_status_id === QuoteStatusEnum::TransactionApproved) {
                CarQuote::withoutEvents(function () use ($lead) {
                    $lead->update([
                        'transaction_approved_at' => now(),
                        'quote_status_date' => now(),
                    ]);
                });
                $dirty = [...$dirty, 'transaction_approved_at' => $lead->transaction_approved_at];
            }
        }

        $this->syncQuote($lead, $dirty);

        if (isset($dirty['quote_status_id']) && $lead->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            try {
                $this->updatePersonalQuote($lead->uuid, QuoteTypeId::Car, $dirty);
            } catch (Exception $e) {
                Log::error('CarQuoteObserver - update personal quote failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $lead->uuid,
                ]);
            }
        }

        if (isset($dirty['quote_status_id']) && $lead->quote_status_id === QuoteStatusEnum::PolicyCancelled) {
            LeadStatusUpdated::dispatch(QuoteTypes::CAR, $lead->uuid);

            try {
                EmbeddedProductRepository::cancelEmbeddedProducts($lead->id, quoteTypeCode::Car);
            } catch (Exception $e) {
                Log::error('CarQuoteObserver - cancel embedded products failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $lead->uuid,
                ]);
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            in_array($lead->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            LeadStatusUpdated::dispatch(QuoteTypes::CAR, $lead->uuid);
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Car, 'quoteUID' => $lead->uuid]);
            MAWelcomeJob::dispatch(
                $lead->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );

            if ($lead->quote_status_id == QuoteStatusEnum::PolicySentToCustomer) {
                try {
                    EmbeddedProductRepository::capturePayment($lead->id, quoteTypeCode::Car);
                } catch (Exception $e) {
                    Log::error('CarQuoteObserver - capture embedded products failed', [
                        'error' => $e->getMessage(),
                        'uuid' => $lead->uuid,
                    ]);
                }
            }
            event(new PrivateClientUpdatedEvent($lead, QuoteTypeId::Car));
        }
        if (
            isset($dirty['quote_status_id']) &&
            $lead->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            LeadStatusUpdated::dispatch(QuoteTypes::CAR, $lead->uuid);
            $payment = $lead->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($lead, $payment, QuoteTypes::CAR->value);
            event(new PrivateClientUpdatedEvent($lead, QuoteTypeId::Car));
        }
    }
}
