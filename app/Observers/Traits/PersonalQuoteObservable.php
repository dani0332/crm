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
use App\Jobs\SendAutomatedHomeRenewalFollowup;
use App\Jobs\SendAutomatedLifeFollowup;
use App\Jobs\SendFICEmailForLife;
use App\Jobs\SendHomeOCBIntroEmailJob;
use App\Models\PersonalQuote;
use App\Models\QuoteFlowDetails;
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
        }

        // ✅ Updated: Trigger on multiple statuses for Home renewal follow-ups
        $allowedRenewalStatuses = [QuoteStatusEnum::Quoted, QuoteStatusEnum::FollowedUp];
        
        if (in_array($personalQuote->quote_status_id, $allowedRenewalStatuses) && 
            $personalQuote->isHome() && 
            $personalQuote->source == LeadSourceEnum::RENEWAL_UPLOAD) {
            
            // Check if we should send follow-up based on frequency and status
            if ($this->shouldSendRenewalFollowup($personalQuote)) {
                SendAutomatedHomeRenewalFollowup::dispatch($personalQuote->uuid)->delay(now()->addSeconds(10));
                LoggerService::info(self::class." - HOME_RENEWAL_AUTOMATED_FOLLOWUPS - Dispatched for Home renewal quote: {$personalQuote->uuid} with status: {$personalQuote->quote_status_id}");
            } else {
                LoggerService::info(self::class." - HOME_RENEWAL_AUTOMATED_FOLLOWUPS - Follow-up not needed for renewal quote {$personalQuote->uuid} (frequency/duplicate check)");
            }
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

        if ($personalQuote->isHome()) {
            info(self::class." - sending home intro email for quote: {$personalQuote->uuid}");
            SendHomeOCBIntroEmailJob::dispatch($personalQuote->uuid)->delay(Carbon::now()->addMinutes(1));
            info(self::class.' - dispatched home intro email - Ref ID:'.$personalQuote->uuid);
            info(self::class." - Old Advisor ID: {$oldAdvisorId} | New Advisor ID: {$personalQuote->advisor_id}");
            if ($personalQuote->source != LeadSourceEnum::IMCRM && ! empty($oldAdvisorId)) {
                if ($oldAdvisorId != $personalQuote->advisor_id) {
                    info(self::class." - Advisor ID updated - Old Advisor ID: {$oldAdvisorId} | New Advisor ID: {$personalQuote->advisor_id}");

                    $emailType = empty($oldAdvisorId) ? 'introductory' : 'reassignment';
                    info(self::class." Sending {$emailType} email to customer for home quote {$personalQuote->uuid}");
                    app(SendEmailCustomerService::class)->sendIntroAndReassignEmail($personalQuote, QuoteTypes::HOME->value, $oldAdvisorId);
                    info(self::class." | {$emailType} email sent to customer for home quote {$personalQuote->uuid}");
                } else {
                    info(self::class." - Advisor ID not updated - Old Advisor ID: {$oldAdvisorId} | New Advisor ID: {$personalQuote->advisor_id}");
                }

            } else {
                info(self::class." - lead source: {$personalQuote->source} |  - Old Advisor ID: {$oldAdvisorId} |  Advisor ID: {$personalQuote->advisor_id}");
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
        $isEligibleForEmail = $personalQuote->source != LeadSourceEnum::IMCRM;

        // for Savings, we need to send email to customer even if the source is IMCRM
        if ($personalQuote->isSavings()) {
            $isEligibleForEmail = true;
        }

        if ($isEligibleForEmail) {
            $quoteType = QuoteTypes::getName($personalQuote->quote_type_id);
            info(self::class." - Quote Type: {$quoteType->value} quote:  {$personalQuote->uuid}");
            $emailType = empty($oldAdvisorId) ? 'introductory' : 'reassignment';
            info(self::class." Sending {$emailType} email to customer for {$quoteType->value} quote {$personalQuote->uuid}");
            app(SendEmailCustomerService::class)->sendIntroAndReassignEmail($personalQuote, $quoteType->value, $oldAdvisorId);
            info(self::class." | {$emailType} email sent to customer for {$quoteType->value} quote {$personalQuote->uuid}");
        }
    }

    /**
     * Check if renewal follow-up should be sent based on OCB condition, frequency and duplicate prevention
     */
    private function shouldSendRenewalFollowup($personalQuote): bool
    {
        // ✅ Status-specific logic with different rules
        if ($personalQuote->quote_status_id == QuoteStatusEnum::Quoted) {
            // First follow-up for "Quoted" status - check OCB condition
            return $this->shouldSendFirstFollowup($personalQuote);
        }

        if ($personalQuote->quote_status_id == QuoteStatusEnum::FollowedUp) {
            // Continuous follow-ups for "FollowedUp" status
            return $this->shouldSendContinuousFollowup($personalQuote);
        }

        LoggerService::info(self::class." - shouldSendRenewalFollowup - Follow-up not approved for status {$personalQuote->quote_status_id}: {$personalQuote->uuid}");
        return false;
    }

    /**
     * Check if first follow-up should be sent (48 hours after OCB)
     */
    private function shouldSendFirstFollowup($personalQuote): bool
    {
        // 1. ✅ Check if OCB was executed on HomeQuote model
        $homeQuote = $personalQuote->homeQuote;
        
        if (!$homeQuote || empty($homeQuote->automated_flow_executed_at)) {
            LoggerService::info(self::class." - shouldSendFirstFollowup - OCB not executed yet for renewal quote: {$personalQuote->uuid}");
            return false;
        }

        // 2. ✅ Check if 48 hours have passed since OCB execution
        $hoursSinceOCB = now()->diffInHours($homeQuote->automated_flow_executed_at);
        if ($hoursSinceOCB < 48) {
            LoggerService::info(self::class." - shouldSendFirstFollowup - Only {$hoursSinceOCB} hours since OCB execution. Need 48 hours for renewal quote: {$personalQuote->uuid}");
            return false;
        }

        // 3. ✅ Check if first follow-up already sent (duplicate prevention)
        $isFollowupExecuted = app(BirdService::class)
            ->isFollowupExecuted($personalQuote->uuid, QuoteTypes::HOME->id(), QuoteFlowType::HOME_RENEWAL_AUTOMATED_FOLLOWUPS->value);

        if ($isFollowupExecuted) {
            LoggerService::info(self::class." - shouldSendFirstFollowup - First follow-up already executed for renewal quote: {$personalQuote->uuid}");
            return false;
        }

        LoggerService::info(self::class." - shouldSendFirstFollowup - All conditions met for first follow-up. Hours since OCB: {$hoursSinceOCB} for renewal quote: {$personalQuote->uuid}");
        return true;
    }

    /**
     * Check if continuous follow-up should be sent (for FollowedUp status)
     */
    private function shouldSendContinuousFollowup($personalQuote): bool
    {
        // 1. ✅ Check frequency control (48 hours between follow-ups)
        $lastFollowup = QuoteFlowDetails::where('quote_uuid', $personalQuote->uuid)
            ->where('flow_type', QuoteFlowType::HOME_RENEWAL_AUTOMATED_FOLLOWUPS->value)
            ->latest('created_at')
            ->first();

        if ($lastFollowup) {
            $hoursSinceLastFollowup = now()->diffInHours($lastFollowup->created_at);
            if ($hoursSinceLastFollowup < 48) {
                LoggerService::info(self::class." - shouldSendContinuousFollowup - Too soon for next follow-up. Hours since last: {$hoursSinceLastFollowup} for renewal quote: {$personalQuote->uuid}");
                return false;
            }
        }

        // 2. ✅ Additional check: Ensure we don't exceed maximum follow-ups (optional)
        $followupCount = QuoteFlowDetails::where('quote_uuid', $personalQuote->uuid)
            ->where('flow_type', QuoteFlowType::HOME_RENEWAL_AUTOMATED_FOLLOWUPS->value)
            ->count();

        // Limit to maximum 5 follow-ups (configurable)
        if ($followupCount >= 5) {
            LoggerService::info(self::class." - shouldSendContinuousFollowup - Maximum follow-ups reached ({$followupCount}) for renewal quote: {$personalQuote->uuid}");
            return false;
        }

        LoggerService::info(self::class." - shouldSendContinuousFollowup - Continuous follow-up approved. Count: {$followupCount}, Hours since last: " . ($lastFollowup ? now()->diffInHours($lastFollowup->created_at) : 'N/A') . " for renewal quote: {$personalQuote->uuid}");
        return true;
    }

}
