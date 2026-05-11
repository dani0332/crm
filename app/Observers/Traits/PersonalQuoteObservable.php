<?php

namespace App\Observers\Traits;

use App\Enums\BranchEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Events\BikeQuoteAdvisorUpdated;
use App\Events\PrivateClientUpdatedEvent;
use App\Events\QuotePolicyBooked;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\ExtendCustomerSubscriptionViaSQS;
use App\Jobs\OCB\SendCyberOCBIntroEmailJob;
use App\Jobs\SendAutomatedHomeRenewalFollowup;
use App\Jobs\SendAutomatedLifeFollowup;
use App\Jobs\SendFICEmailForLife;
use App\Jobs\SendHomeOCBIntroEmailJob;
use App\Jobs\SendOCAEmailJob;
use App\Jobs\SendPolicyIssueWhatsappMessageJob;
use App\Jobs\SendSavingsOCAEmailJob;
use App\Models\PersonalQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\PaymentRepository;
use App\Services\BirdService;
use App\Services\BranchAssignmentService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\SendEmailCustomerService;
use App\Traits\QuoteTraits\QuoteAllocatable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

trait PersonalQuoteObservable
{
    use QuoteAllocatable;

    private const LOG_PRIVATE_CLIENT_UPDATED_FAILED = 'PersonalQuoteObserver - dispatch PrivateClientUpdatedEvent failed';
    private const LOG_BIKE_ADVISOR_UPDATED_FAILED = 'PersonalQuoteObserver - dispatch BikeQuoteAdvisorUpdated event failed';

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
            if ($personalQuote->quote_status_id === QuoteStatusEnum::PolicyCancelled &&
            ($personalQuote->isBike() || $personalQuote->isHome() || $personalQuote->isCyber())) {
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

        if (in_array($personalQuote->quote_status_id, [QuoteStatusEnum::Quoted, QuoteStatusEnum::ApplicationPending, QuoteStatusEnum::PaymentPending]) &&
            $personalQuote->isHome() &&
            $personalQuote->source == LeadSourceEnum::RENEWAL_UPLOAD) {

            $isFollowupExecuted = app(BirdService::class)
                ->isFollowupExecuted($personalQuote->uuid, QuoteTypes::HOME->id(), QuoteFlowType::HOME_RENEWAL_AUTOMATED_FOLLOWUPS->value);

            if ($isFollowupExecuted) {
                LoggerService::info(self::class." - HOME_RENEWAL_AUTOMATED_FOLLOWUPS - Followup already executed {$personalQuote->uuid}");

                return;
            }
            SendAutomatedHomeRenewalFollowup::dispatch($personalQuote->uuid)->delay(now()->addSeconds(10));

            LoggerService::info(self::class." - HOME_RENEWAL_AUTOMATED_FOLLOWUPS - Dispatched for Home renewal quote: {$personalQuote->uuid}");
        }

        if ($personalQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            try {
                event(new PrivateClientUpdatedEvent($personalQuote, $personalQuote->quote_type_id));
            } catch (Exception $e) {
                LoggerService::warning(self::LOG_PRIVATE_CLIENT_UPDATED_FAILED, [
                    'uuid' => $personalQuote->uuid,
                    'quote_status_id' => $personalQuote->quote_status_id,
                ], exception: $e);
            }
        }

        if ($personalQuote->quote_status_id === QuoteStatusEnum::PolicyIssued) {
            $this->handlePolicyIssued($personalQuote);
            $allowedQuoteTypes = [QuoteTypes::HOME->id(), QuoteTypes::CYBER->id()];
            if (in_array($personalQuote->quote_type_id, $allowedQuoteTypes)) {
                LoggerService::info(self::class.' fn:'.__FUNCTION__.' - Quote Code '.$personalQuote->code.' Policy Issued ');
                SendPolicyIssueWhatsappMessageJob::dispatch($personalQuote->uuid, $personalQuote->quote_type_id)->onQueue('insly');
            }

        }

        $this->handleStaleRemovalFromLeads($personalQuote);
    }

    protected function handleAdvisorChange(PersonalQuote $personalQuote, $oldAdvisorId = null): void
    {
        $personalQuote->markLeadAllocationPassed();

        if ($personalQuote->isBike()) {
            try {
                event(new BikeQuoteAdvisorUpdated($personalQuote, $oldAdvisorId));
            } catch (Exception $e) {
                LoggerService::warning(self::LOG_BIKE_ADVISOR_UPDATED_FAILED, [
                    'uuid' => $personalQuote->uuid,
                    'old_advisor_id' => $oldAdvisorId,
                    'new_advisor_id' => $personalQuote->advisor_id,
                ], exception: $e);
            }
        }

        if ($personalQuote->isSavings()) {

            if (empty($oldAdvisorId) && ! $personalQuote->isNonAdvisorEmailSent()) {
                SendSavingsOCAEmailJob::dispatch($personalQuote->uuid)->delay(Carbon::now()->addMinutes(1));
                LoggerService::info(self::class." - OCA email job dispatched for savings quote {$personalQuote->uuid} (first assignment)");
            } else {
                LoggerService::info(self::class." - Sending reassignment email for savings quote {$personalQuote->uuid} (reassignment from advisor {$oldAdvisorId})");
                app(SendEmailCustomerService::class)->sendIntroAndReassignEmail(
                    $personalQuote,
                    QuoteTypes::SAVINGS->value,
                    $oldAdvisorId
                );
                LoggerService::info(self::class." - Reassignment email sent to customer for savings quote {$personalQuote->uuid}");
            }
        }

        if ($personalQuote->isPet() || $personalQuote->isYacht() || $personalQuote->isCycle()) {
            $this->IntroAndReassignEmail($personalQuote, $oldAdvisorId);
        }
        if ($personalQuote->isLife()) {
            if ($personalQuote->isFIC(quoteType: QuoteTypes::LIFE)) {
                SendFICEmailForLife::dispatch($personalQuote->uuid)->delay(now()->addSeconds(10));
                LoggerService::info(self::class." - FIC email sent to customer for life quote {$personalQuote->uuid}");
            } else {
                SendOCAEmailJob::dispatch($personalQuote->uuid, []);
                LoggerService::info(self::class." - OCA email sent to customer for life quote {$personalQuote->uuid}");
            }
        }
        if ($personalQuote->isCyber()) {
            SendCyberOCBIntroEmailJob::dispatch($personalQuote->uuid)->delay(now()->addSeconds(10));
            LoggerService::info(self::class." - OCB Intro Email sent to customer for device quote {$personalQuote->uuid}");
        }

        $this->handleIntroEmails($personalQuote, $oldAdvisorId);
    }

    protected function handleIntroEmails(PersonalQuote $personalQuote, $oldAdvisorId = null): void
    {

        if ($personalQuote->isHome()) {
            LoggerService::info(self::class." - sending home intro email for quote: {$personalQuote->uuid} Quote Status: {$personalQuote->quote_status_id}");
            SendHomeOCBIntroEmailJob::dispatch($personalQuote->uuid)->delay(Carbon::now()->addMinutes(1));
            LoggerService::info(self::class.' - dispatched home intro email - Ref ID:'.$personalQuote->uuid);
            LoggerService::info(self::class." - Old Advisor ID: {$oldAdvisorId} | New Advisor ID: {$personalQuote->advisor_id}");
            if (! $personalQuote->isSuppressIntroEmail() && $personalQuote->source != LeadSourceEnum::IMCRM && ! empty($oldAdvisorId)) {
                if ($oldAdvisorId != $personalQuote->advisor_id) {
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

    private function updatePersonalQuote(PersonalQuote $personalQuote, array $data, bool $withEvents = false): void
    {
        if (! $withEvents) {
            PersonalQuote::withoutEvents(function () use ($personalQuote, $data) {
                $personalQuote->update($data);
            });
        } else {
            $personalQuote->update($data);
        }
    }

    private function handleTransactionApproved(PersonalQuote $personalQuote): void
    {
        $this->updatePersonalQuote($personalQuote, ['transaction_approved_at' => now()]);
        $this->handleRevivalQuote($personalQuote);
    }

    private function handlePolicyIssued(PersonalQuote $personalQuote): void
    {
        $payment = $personalQuote->payments()->mainLeadPayment()->first();
        (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($personalQuote, $payment, QuoteTypes::PERSONAL->value);
    }

    private function handlePolicyBookedOrSentToCustomer(PersonalQuote $personalQuote): void
    {
        CourtesyEmailJob::dispatch(['quoteTypeId' => $personalQuote->quote_type_id, 'quoteUID' => $personalQuote->uuid]);
        ExtendCustomerSubscriptionViaSQS::dispatch(
            $personalQuote->customer,
            'LEAD_STATUS_UPDATE',
            'lead-status-update-myalfred-we'
        );

        if ($personalQuote->isHome() || (($personalQuote->isBike() || $personalQuote->isDevice() || $personalQuote->isCyber()) && $personalQuote->quote_status_id == QuoteStatusEnum::PolicySentToCustomer)) {

            try {
                EmbeddedProductRepository::capturePayment($personalQuote->id, QuoteTypes::getName($personalQuote->quote_type_id)->value);
            } catch (Exception $e) {
                Log::error('PersonalQuoteObserver - capture embedded products failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $personalQuote->uuid,
                ]);
            }
        }

        if ($personalQuote->quote_status_id == QuoteStatusEnum::PolicyBooked) {
            try {
                app(BranchAssignmentService::class)->saveBranchOverride($personalQuote, $personalQuote->quote_type_id);
                PersonalQuote::withoutEvents(function () use ($personalQuote) {

                    $shouldValidateBranch = app(PolicyIssuanceService::class)->shouldValidateBranch($personalQuote, QuoteTypes::getName($personalQuote->quote_type_id)->value);
                    $branch_id = null;
                    if ($shouldValidateBranch) {
                        $branch = app(BranchAssignmentService::class)->getBranch($personalQuote?->advisor?->primaryBranch?->branch_id, $personalQuote->quote_type_id);
                        $branch_id = $branch?->id;
                    } else {
                        $branch_id = BranchEnum::DUBAI->value;
                    }

                    $personalQuote->update([
                        'branch_id' => $branch_id,
                    ]);
                });
            } catch (Exception $e) {
                LoggerService::error('PersonalQuoteObserver - save branch data failed', [
                    'uuid' => $personalQuote->uuid,
                ], exception: $e);
            }

            try {
                QuotePolicyBooked::dispatch($personalQuote->uuid, $personalQuote->quote_type_id);
            } catch (Exception $e) {
                LoggerService::error('PersonalQuoteObserver - dispatch QuotePolicyBooked event failed', [
                    'uuid' => $personalQuote->uuid,
                ], exception: $e);
            }
        }
    }

    private function handleBikePolicyCancelled(PersonalQuote $personalQuote): void
    {
        try {
            EmbeddedProductRepository::cancelEmbeddedProducts($personalQuote->id, QuoteTypes::getName($personalQuote->quote_type_id)->value);
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

        if (! $personalQuote->isSuppressIntroEmail() && $isEligibleForEmail) {
            $quoteType = QuoteTypes::getName($personalQuote->quote_type_id);
            LoggerService::info(self::class." - Quote Type: {$quoteType->value} quote:  {$personalQuote->uuid}");
            $emailType = empty($oldAdvisorId) ? 'introductory' : 'reassignment';
            LoggerService::info(self::class." Sending {$emailType} email to customer for {$quoteType->value} quote {$personalQuote->uuid}");
            app(SendEmailCustomerService::class)->sendIntroAndReassignEmail($personalQuote, $quoteType->value, $oldAdvisorId);
            LoggerService::info(self::class." | {$emailType} email sent to customer for {$quoteType->value} quote {$personalQuote->uuid}");
        }
    }

    private function handleRevivalQuote(PersonalQuote $personalQuote): void
    {
        LoggerService::info(self::class.' - handleRevivalQuote - Quote Type Request Received', [
            'uuid' => $personalQuote->uuid,
        ]);
        if ($personalQuote->quote_type_id == (int) QuoteTypes::LIFE->id() && in_array($personalQuote->source, [LeadSourceEnum::REVIVAL, LeadSourceEnum::REVIVAL_REPLIED])) {
            $this->updatePersonalQuote($personalQuote, ['source' => LeadSourceEnum::REVIVAL_PAID], withEvents: true);
            LoggerService::info(self::class.' - handleRevivalQuote - Quote Type Updated to Revival Paid', [
                'quote_type_id' => $personalQuote->quote_type_id,
                'uuid' => $personalQuote->uuid,
                'quote_status_id' => $personalQuote->quote_status_id,
                'source' => $personalQuote->source,
            ]);
        }
    }

}
