<?php

namespace App\Observers\Traits;

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
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

trait PersonalQuoteObservable
{
    protected function handleQuoteStatusChange(PersonalQuote $personalQuote): void
    {
        if (checkPersonalQuotes($personalQuote->quoteType?->code)) {
            if ($personalQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
                $this->handleTransactionApproved($personalQuote);
            }

            if (in_array($personalQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])) {
                $this->handlePolicyBookedOrSentToCustomer($personalQuote);
            }

            // For now PolicyCancelled Handling is only for Bike
            if ($personalQuote->quote_status_id === QuoteStatusEnum::PolicyCancelled && $personalQuote->isBike()) {
                $this->handleBikePolicyCancelled($personalQuote);
            }
        }

        if ($personalQuote->quote_status_id === QuoteStatusEnum::PolicyIssued) {
            $this->handlePolicyIssued($personalQuote);
        }

        $this->handleStaleRemovalFromLeads($personalQuote);
    }

    protected function handleAdvisorChange(PersonalQuote $personalQuote): void
    {
        $personalQuote->markLeadAllocationPassed();

        if ($personalQuote->isBike()) {
            $oldAdvisorId = $personalQuote->getOriginal('advisor_id');
            event(new BikeQuoteAdvisorUpdated($personalQuote, $oldAdvisorId));
        }
    }

    protected function handleIntroEmails(PersonalQuote $personalQuote): void
    {
        if ($personalQuote->isHome()) {
            info(self::class." - sending home intro email for quote: {$personalQuote->uuid} | Time: ".now());
            SendHomeOCBIntroEmailJob::dispatch($personalQuote->uuid)->delay(Carbon::now()->addMinutes(1));
            info(self::class.' - dispatched home intro email - Ref ID:'.$personalQuote->uuid.' | Time: '.now());
        }
    }

    private function updatePersonalQuote(PersonalQuote $personalQuote, array $data): void
    {
        PersonalQuote::withoutEvents(function () use ($personalQuote, $data) {
            $personalQuote->update($data);
        });
    }

    private function handleTransactionApproved(PersonalQuote $personalQuote): void
    {
        $this->updatePersonalQuote($personalQuote, ['transaction_approved_at' => now()]);
    }

    private function handlePolicyIssued(PersonalQuote $personalQuote): void
    {
        $payment = $personalQuote->payments()->mainLeadPayment()->first();
        (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($personalQuote, $payment, QuoteTypes::PERSONAL->value);
    }

    private function handlePolicyBookedOrSentToCustomer(PersonalQuote $personalQuote): void
    {
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

    private function handleBikePolicyCancelled(PersonalQuote $personalQuote): void
    {
        try {
            EmbeddedProductRepository::cancelEmbeddedProducts($personalQuote->id, quoteTypeCode::Bike);
        } catch (Exception $e) {
            Log::error('PersonalQuoteObserver - cancel embedded products failed', [
                'error' => $e->getMessage(),
                'uuid' => $personalQuote->uuid,
            ]);
        }
    }

    private function handleStaleRemovalFromLeads(PersonalQuote $personalQuote): void
    {
        if (
            $this->removeStaleFromLead($personalQuote->quote_status_id)
            && (
                $personalQuote->isPet()
                || $personalQuote->isCycle()
                || $personalQuote->isYacht()
                || $personalQuote->isHome()
            )
        ) {
            $this->updatePersonalQuote($personalQuote, ['stale_at' => null]);
        }
    }
}
