<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Events\Health\HealthTransactionApproved;
use App\Events\HealthQuoteAdvisorUpdated;
use App\Events\PrivateClientUpdatedEvent;
use App\Events\QuotePolicyBooked;
use App\Jobs\Audit\LogAllocation;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\ExtendCustomerSubscriptionViaSQS;
use App\Jobs\Health\SendApplicationSubmittedEmailJob;
use App\Jobs\IntroEmailJob;
use App\Jobs\OCAHealthFollowupEmailJob;
use App\Jobs\SendPolicyIssueWhatsappMessageJob;
use App\Models\ApplicationStorage;
use App\Models\HealthQuote;
use App\Repositories\PaymentRepository;
use App\Services\BranchAssignmentService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteStatusLogService;
use App\Services\SLA\SLAService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteSyncTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class HealthQuoteObserver
{
    use GenericQueriesAllLobs, PersonalQuoteSyncTrait;

    private const LOG_HEALTH_TRANSACTION_APPROVED_FAILED = 'HealthQuoteObserver - dispatch HealthTransactionApproved event failed';
    private const LOG_PRIVATE_CLIENT_UPDATED_FAILED = 'HealthQuoteObserver - dispatch PrivateClientUpdatedEvent failed';

    public function updating(HealthQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id')) {
            app(QuoteStatusLogService::class)->createQuoteStatusLog(
                QuoteTypeId::Health,
                $quote,
                $quote->getOriginal('quote_status_id'),
            );

            if (! $quote->isDirty('quote_status_date')) {
                $quote->quote_status_date = now();
            }
        }
    }

    /**
     * Handle the HealthQuote "updated" event.
     *
     * - Any changes that adds business logic should be enclosed in try-catch block or executed in queue.
     */
    public function updated(HealthQuote $healthQuote): void
    {
        $dirty = $healthQuote->getDirty();

        if (
            isset($dirty['quote_status_id']) &&
            $healthQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            // Trigger the event for transaction approval
            try {
                HealthTransactionApproved::dispatch($healthQuote);
            } catch (Exception $e) {
                LoggerService::warning(self::LOG_HEALTH_TRANSACTION_APPROVED_FAILED, [
                    'uuid' => $healthQuote->uuid,
                    'quote_status_id' => $healthQuote->quote_status_id,
                ], exception: $e);
            }
            $dirty = [...$dirty, 'transaction_approved_at' => $healthQuote->transaction_approved_at, 'is_quote_locked' => true];
        }

        if (isset($dirty['advisor_id'])) {
            try {
                info(self::class." - Going to dispatch HealthQuoteAdvisorUpdated event for uuid {$healthQuote->uuid}", [
                    'current_advisor_id' => $healthQuote->advisor_id,
                    'original_advisor_id' => $healthQuote->getOriginal('advisor_id'),
                ]);

                LogAllocation::dispatch($healthQuote, QuoteTypes::HEALTH);

                HealthQuoteAdvisorUpdated::dispatch($healthQuote, $healthQuote->getOriginal('advisor_id'));
                $healthQuote->markLeadAllocationPassed();

                app(SLAService::class)->initiateSLATracking(QuoteTypes::HEALTH, $healthQuote);
            } catch (Exception $e) {
                Log::error('HealthQuoteObserver - handle health update advisor failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $healthQuote->uuid,
                ]);
            }

        }

        if (
            isset($dirty['quote_status_id'])
        ) {
            if ($healthQuote->quote_status_id === QuoteStatusEnum::ApplicationSubmitted) {
                SendApplicationSubmittedEmailJob::dispatch($healthQuote);
            }
            $eligibleStatuses = [QuoteStatusEnum::Quoted, QuoteStatusEnum::ApplicationPending];
            if (in_array($healthQuote->quote_status_id, $eligibleStatuses)) {
                $healthAutoFollowupSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::HEALTH_AUTOMATED_FOLLOWUPS_SWITCH)->first();
                // Send Automated Followup Email Job if Health Auto-Followups is enabled.
                if ($healthAutoFollowupSwitch && $healthAutoFollowupSwitch->value == 1) {
                    $delayDays = isLeadSic($healthQuote->uuid) ? 3 : 2;
                    if ($healthQuote->source != LeadSourceEnum::RENEWAL_UPLOAD) {
                        $delay = app()->isProduction()
                            ? Carbon::now()->addDays($delayDays)
                            : Carbon::now()->addMinutes($delayDays);
                        OCAHealthFollowupEmailJob::dispatch($healthQuote->uuid)->delay($delay);
                        LoggerService::info('OCAHealthFollowupEmailJob dispatched ');
                    }

                }
            }

        }

        if (isset($dirty['quote_status_id']) && $this->removeStaleFromLead($healthQuote->quote_status_id)) {
            HealthQuote::withoutEvents(function () use ($healthQuote) {
                $healthQuote->update(['stale_at' => null]);
            });
            $dirty = [...$dirty, 'stale_at' => $healthQuote->stale_at];
        }

        if (isset($dirty['quote_status_id']) && $healthQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            try {
                $this->updatePersonalQuote($healthQuote->uuid, QuoteTypeId::Health, $dirty);
            } catch (Exception $e) {
                Log::error('HealthQuoteObserver - update personal quote failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $healthQuote->uuid,
                ]);
            }

            try {
                HealthQuote::withoutEvents(function () use ($healthQuote, &$dirty) {
                    $branch = app(BranchAssignmentService::class)->getBranch($healthQuote?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Health, $healthQuote->emirate_of_your_visa_id);
                    $healthQuote->update([
                        'branch_id' => $branch?->id,
                    ]);
                    $dirty = [...$dirty, 'branch_id' => $branch?->id];
                });
            } catch (Exception $e) {
                LoggerService::error('HealthQuoteObserver - save branch data failed', [
                    'uuid' => $healthQuote->uuid,
                ], exception: $e);
            }
        }

        $this->syncQuote($healthQuote, $dirty);

        $introEmailEligibleStatuses = [QuoteStatusEnum::NewLead, QuoteStatusEnum::Qualified, QuoteStatusEnum::Quoted];
        if (isset($dirty['advisor_id']) && in_array($healthQuote->quote_status_id, $introEmailEligibleStatuses)) {
            info("Quote status changed to {$healthQuote->quote_status_id} | Ref-ID: {$healthQuote->uuid} | Time: ".now());
            IntroEmailJob::dispatch(quoteTypeCode::Health, 'Capi', $healthQuote->uuid, 'send-rm-intro-email', null, false);
        }
        if (
            isset($dirty['quote_status_id']) &&
            in_array($healthQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Health, 'quoteUID' => $healthQuote->uuid]);
            ExtendCustomerSubscriptionViaSQS::dispatch(
                $healthQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }

        if (
            isset($dirty['quote_status_id']) &&
            $healthQuote->quote_status_id === QuoteStatusEnum::PolicyBooked
        ) {
            try {
                event(new PrivateClientUpdatedEvent($healthQuote, QuoteTypeId::Health));
            } catch (Exception $e) {
                LoggerService::warning(self::LOG_PRIVATE_CLIENT_UPDATED_FAILED, [
                    'uuid' => $healthQuote->uuid,
                    'quote_status_id' => $healthQuote->quote_status_id,
                ], exception: $e);
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            $healthQuote->quote_status_id === QuoteStatusEnum::PolicyBooked
        ) {
            try {
                QuotePolicyBooked::dispatch($healthQuote->uuid, QuoteTypeId::Health);
            } catch (Exception $e) {
                LoggerService::error('HealthQuoteObserver - dispatch QuotePolicyBooked event failed', [], $e, ['ref_id' => $healthQuote->uuid]);
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            $healthQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            SendPolicyIssueWhatsappMessageJob::dispatch($healthQuote->uuid, QuoteTypes::HEALTH->id())->onQueue('insly');
            $payment = $healthQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($healthQuote, $payment, QuoteTypes::HEALTH->value);
        }

        if (isset($dirty['quote_status_id'])) {
            app(SLAService::class)->meetSLAOnStatusUpdate($healthQuote);
        }

        if (isset($dirty['aml_status'])) {
            app(SLAService::class)->meetSLAOnAMLStatusUpdate($healthQuote);
        }

        if (isset($dirty['kyc_decision'])) {
            app(SLAService::class)->meetSLAOnKYCStatusUpdate($healthQuote);
        }
    }
}
