<?php

namespace App\Observers\Traits;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Events\BikeQuoteAdvisorUpdated;
use App\Events\PrivateClientUpdatedEvent;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Jobs\SendAutomatedLifeFollowup;
use App\Jobs\SendFICEmailForLife;
use App\Jobs\SendHomeOCBIntroEmailJob;
use App\Models\PersonalQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\PaymentRepository;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use App\Traits\QuoteTraits\QuoteAllocatable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

trait PersonalQuoteObservable
{
    use QuoteAllocatable;
    protected function handleQuoteStatusChange(PersonalQuote $personalQuote): void
    {
        if (checkPersonalQuotes($personalQuote->quoteType?->code)) {
            if ($personalQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
                $this->handleTransactionApproved($personalQuote);
            }

            if (in_array($personalQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])) {
                $this->handlePolicyBookedOrSentToCustomer($personalQuote);
                event(new PrivateClientUpdatedEvent($personalQuote, $personalQuote->quote_type_id));
            }

            // For now PolicyCancelled Handling is only for Bike
            if ($personalQuote->quote_status_id === QuoteStatusEnum::PolicyCancelled &&
            ($personalQuote->isBike() || $personalQuote->isHome())) {
                $this->handleBikePolicyCancelled($personalQuote);
            }
        }

        if ($personalQuote->quote_status_id == QuoteStatusEnum::Quoted && $personalQuote->isLife()) {
            $isFollowupExecuted = app(BirdService::class)
                ->isFollowupExecuted($personalQuote->uuid, QuoteTypes::LIFE->id(), QuoteFlowType::LIFE_AUTOMATED_FOLLOWUPS->value);

            if ($isFollowupExecuted) {
                LoggerService::info(self::class." - LIFE_AUTOMATED_FOLLOWUPS - Followup already executed {$personalQuote->uuid}");

                return;
            }
            SendAutomatedLifeFollowup::dispatch($personalQuote->uuid)->delay(now()->addSeconds(10));
        } else {
            LoggerService::info(self::class." - Quote status is {$personalQuote->quote_status_id} for quote: {$personalQuote->uuid}");
        }

        if ($personalQuote->quote_status_id === QuoteStatusEnum::PolicyIssued) {
            $this->handlePolicyIssued($personalQuote);
            event(new PrivateClientUpdatedEvent($personalQuote, $personalQuote->quote_type_id));
        }

        $this->handleStaleRemovalFromLeads($personalQuote);
    }

    protected function handleAdvisorChange(PersonalQuote $personalQuote, $oldAdvisorId = null): void
    {
        $personalQuote->markLeadAllocationPassed();

        if ($personalQuote->isBike()) {
            event(new BikeQuoteAdvisorUpdated($personalQuote, $oldAdvisorId));
        }

        if ($personalQuote->isPet() || $personalQuote->isYacht() || $personalQuote->isCycle() || $personalQuote->isSavings()) {
            $this->IntroAndReassignEmail($personalQuote, $oldAdvisorId);
        }
        if ($personalQuote->isLife()) {
            if ($personalQuote->isFIC(quoteType: QuoteTypes::LIFE)) {
                SendFICEmailForLife::dispatch($personalQuote->uuid)->delay(now()->addSeconds(10));
            }
        }

        $this->handleIntroEmails($personalQuote, $oldAdvisorId);
    }

    protected function handleIntroEmails(PersonalQuote $personalQuote, $oldAdvisorId = null): void
    {

        if ( $personalQuote->isHome()) {
            LoggerService::info(self::class." - sending home intro email for quote: {$personalQuote->uuid} Quote Status: {$personalQuote->quote_status_id}");
            SendHomeOCBIntroEmailJob::dispatch($personalQuote->uuid)->delay(Carbon::now()->addMinutes(1));
            LoggerService::info(self::class.' - dispatched home intro email - Ref ID:'.$personalQuote->uuid);
            LoggerService::info(self::class." - Old Advisor ID: {$oldAdvisorId} | New Advisor ID: {$personalQuote->advisor_id}");
            if (!suppressIntroEmailByStatus($personalQuote->quote_status_id) && $personalQuote->source != LeadSourceEnum::IMCRM && ! empty($oldAdvisorId)) {
                if (  $oldAdvisorId != $personalQuote->advisor_id) {
                    LoggerService::info(self::class." - Advisor ID updated - Old Advisor ID: {$oldAdvisorId} | New Advisor ID: {$personalQuote->advisor_id}");

                    $emailType = empty($oldAdvisorId) ? 'introductory' : 'reassignment';
                    LoggerService::info(self::class." Sending {$emailType} email to customer for home quote {$personalQuote->uuid}");
                    app(SendEmailCustomerService::class)->sendIntroAndReassignEmail($personalQuote, QuoteTypes::HOME->value, $oldAdvisorId);
                    LoggerService::info(self::class." | {$emailType} email sent to customer for home quote {$personalQuote->uuid}");
                } else {
                    LoggerService::info(self::class." - Advisor ID not updated - Old Advisor ID: {$oldAdvisorId} | New Advisor ID: {$personalQuote->advisor_id}");
                }

            } else {
                LoggerService::info(self::class." - lead source: {$personalQuote->source} |  - Old Advisor ID: {$oldAdvisorId} |  Advisor ID: {$personalQuote->advisor_id} Quote Status: {$personalQuote->quote_status_id}");
            }
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

        if ($personalQuote->isHome() || ($personalQuote->isBike() && $personalQuote->quote_status_id == QuoteStatusEnum::PolicySentToCustomer)) {
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

    private function IntroAndReassignEmail(PersonalQuote $personalQuote, $oldAdvisorId = null): void
    {
        $isEligibleForEmail =  $personalQuote->source != LeadSourceEnum::IMCRM;

        // for Savings, we need to send email to customer even if the source is IMCRM
        if ($personalQuote->isSavings()) {
            $isEligibleForEmail = true;
        }

        if (!suppressIntroEmailByStatus($personalQuote->quote_status_id) && $isEligibleForEmail) {
            $quoteType = QuoteTypes::getName($personalQuote->quote_type_id);
            info(self::class." - Quote Type: {$quoteType->value} quote:  {$personalQuote->uuid}");
            $emailType = empty($oldAdvisorId) ? 'introductory' : 'reassignment';
            info(self::class." Sending {$emailType} email to customer for {$quoteType->value} quote {$personalQuote->uuid}");
            app(SendEmailCustomerService::class)->sendIntroAndReassignEmail($personalQuote, $quoteType->value, $oldAdvisorId);
            info(self::class." | {$emailType} email sent to customer for {$quoteType->value} quote {$personalQuote->uuid}");
        }
    }

}
