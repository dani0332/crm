<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\BranchEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Events\PrivateClientUpdatedEvent;
use App\Events\QuotePolicyBooked;
use App\Events\TravelQuoteAdvisorUpdated;
use App\Jobs\Audit\LogAllocation;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\ExtendCustomerSubscriptionViaSQS;
use App\Jobs\SendFailedPaymentEmailJob;
use App\Jobs\SendPolicyIssueWhatsappMessageJob;
use App\Models\TravelQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\PaymentRepository;
use App\Services\BranchAssignmentService;
use App\Services\EmailServices\TravelEmailService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\QuoteJourneyService;
use App\Services\QuoteStatusLogService;
use App\Services\SIBService;
use App\Traits\PersonalQuoteSyncTrait;
use Exception;
use Illuminate\Support\Facades\Log;

class TravelQuoteObserver
{
    use PersonalQuoteSyncTrait;

    private const LOG_PRIVATE_CLIENT_UPDATED_FAILED = 'TravelQuoteObserver - dispatch PrivateClientUpdatedEvent failed';

    public function updating(TravelQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id')) {
            app(QuoteStatusLogService::class)->createQuoteStatusLog(
                QuoteTypeId::Travel,
                $quote,
                $quote->getOriginal('quote_status_id'),
            );

            if (! $quote->isDirty('quote_status_date')) {
                $quote->quote_status_date = now();
            }
        }
    }

    /**
     * Handle the TravelQuote "updated" event.
     *
     * - Any changes that adds business logic should be enclosed in try-catch block or executed in queue.
     */
    public function updated(TravelQuote $travelQuote): void
    {
        $dirty = $travelQuote->getDirty();
        $changes = [];
        $quoteStatusChanged = isset($dirty['quote_status_id']);
        $isPolicyBooked = $travelQuote->quote_status_id === QuoteStatusEnum::PolicyBooked;
        $hasPolicyBookedStatusChange = ($travelQuote->wasChanged('quote_status_id') || $quoteStatusChanged) && $isPolicyBooked;
        $hasPolicySentOrBookedStatusChange = $quoteStatusChanged
            && in_array($travelQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked], true);

        foreach ($dirty as $attribute => $value) {
            $changes[$attribute] = [
                'old' => $travelQuote->getOriginal($attribute),
                'new' => $value,
            ];
        }

        LoggerService::info('TravelQuoteObserver  - quoteStatusChanged', [
            'uuid' => $travelQuote->uuid,
            'quoteStatusChanged' => $quoteStatusChanged,
            'isPolicyBooked' => $isPolicyBooked,
            'isPolicyBookedChanged' => $travelQuote->quote_status_id == QuoteStatusEnum::PolicyBooked,
            'hasPolicyBookedStatusChange' => $hasPolicyBookedStatusChange,
            'hasPolicySentOrBookedStatusChange' => $hasPolicySentOrBookedStatusChange,
            'dirty' => $dirty,
        ]);

        if ($hasPolicyBookedStatusChange) {
            LoggerService::info('TravelQuoteObserver -  inside policy booked check with quote id : '.$travelQuote->uuid, [
                'uuid' => $travelQuote->uuid,
                'quote_status_id' => $travelQuote->quote_status_id,
                'was_changed_quote_status_id' => $travelQuote->wasChanged('quote_status_id'),
                'isset_quote_status_id' => $quoteStatusChanged,
            ]);

            try {
                LoggerService::info('TravelQuoteObserver - completing quote journey entry for quote uuid: '.$travelQuote->uuid);
                app(QuoteJourneyService::class)->completePolicyIssuanceEntry($travelQuote->uuid, QuoteTypeId::Travel);
            } catch (Exception $e) {
                LoggerService::error('TravelQuoteObserver - complete quote journey entry failed', [
                    'uuid' => $travelQuote->uuid,
                ], exception: $e);
            }
        }

        if ($this->shouldStopSIC($dirty, $travelQuote)) {
            // Implement your logic to stop SIC follow-up emails here
            LoggerService::info(self::class." - Stopping SIC follow-up emails for quote uuid: {$travelQuote->uuid} with status: {$travelQuote->quote_status_id} and payment status: {$travelQuote->payment_status}");
            $sicEventName = getAppStorageValueByKey(ApplicationStorageEnums::SIC_TRAVEL_WORKFLOW_DISABLE);
            if ($sicEventName) {
                SIBService::createWorkflowEvent($sicEventName, $travelQuote);
                LoggerService::info(self::class." - SIC workflow stopped for lead uuid : {$travelQuote->uuid}");
            } else {
                LoggerService::info(self::class.' - SIC workflow key not found');
            }
        }
        if (isset($dirty['advisor_id'])) {
            try {
                $travelQuote->markLeadAllocationPassed();

                SendFailedPaymentEmailJob::dispatch($travelQuote->uuid, QuoteTypes::TRAVEL);

                LogAllocation::dispatch($travelQuote, QuoteTypes::TRAVEL);

                // If advisor is not CHS advisor, then send FTC email
                if (isCHSAdvisor($dirty['advisor_id'])) {
                    info(self::class." - Advisor is CHS advisor for uuid: {$travelQuote->uuid} so not sending FTC email");
                } else {
                    $oldAdvisorId = $changes['advisor_id']['old'];
                    TravelQuoteAdvisorUpdated::dispatch($travelQuote, $oldAdvisorId);
                }
            } catch (Exception $e) {
                Log::error('TravelQuoteObserver - travel quote advisor updated failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $travelQuote->uuid,
                ]);
            }
        }

        if ($quoteStatusChanged && $travelQuote->quote_status_id === QuoteStatusEnum::TransactionApproved) {
            TravelQuote::withoutEvents(function () use ($travelQuote) {
                $travelQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $travelQuote->transaction_approved_at];
        }
        if ($quoteStatusChanged && $travelQuote->quote_status_id === QuoteStatusEnum::Quoted && $travelQuote->source != LeadSourceEnum::RENEWAL_UPLOAD) {
            LoggerService::info(self::class." - Sending automated travel followup for quote uuid: {$travelQuote->uuid}");
            app(TravelEmailService::class)->handleAutomatedFollowup($travelQuote);
        }

        if ($hasPolicyBookedStatusChange) {
            LoggerService::info('TravelQuoteObserver -  inside policy booked check with quote id : '.$travelQuote->uuid, [
                'uuid' => $travelQuote->uuid,
                'quote_status_id' => $travelQuote->quote_status_id,
                'was_changed_quote_status_id' => $travelQuote->wasChanged('quote_status_id'),
                'isset_quote_status_id' => $quoteStatusChanged,
            ]);
            try {
                $this->updatePersonalQuote($travelQuote->uuid, QuoteTypeId::Travel, $dirty);
            } catch (Exception $e) {
                Log::error('TravelQuoteObserver - update personal quote failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $travelQuote->uuid,
                ]);
            }

            try {
                app(BranchAssignmentService::class)->saveBranchOverride($travelQuote, QuoteTypeId::Travel);
                TravelQuote::withoutEvents(function () use ($travelQuote, &$dirty) {

                    $shouldValidateBranch = app(PolicyIssuanceService::class)->shouldValidateBranch($travelQuote, QuoteTypes::TRAVEL->value);
                    $branch_id = null;
                    if ($shouldValidateBranch) {
                        $branch = app(BranchAssignmentService::class)->getBranch($travelQuote?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Travel);
                        $branch_id = $branch?->id;
                    } else {
                        $branch_id = BranchEnum::DUBAI->value;
                    }

                    $travelQuote->update([
                        'branch_id' => $branch_id,
                    ]);
                    $dirty = [...$dirty, 'branch_id' => $branch_id];
                });
            } catch (Exception $e) {
                LoggerService::error('TravelQuoteObserver - save branch data failed', [
                    'uuid' => $travelQuote->uuid,
                ], exception: $e);
            }
        }

        $this->syncQuote($travelQuote, $dirty);

        if ($quoteStatusChanged && $travelQuote->quote_status_id === QuoteStatusEnum::PolicyCancelled) {
            try {
                EmbeddedProductRepository::cancelEmbeddedProducts($travelQuote->id, quoteTypeCode::Travel);
            } catch (Exception $e) {
                LoggerService::error('TravelQuoteObserver - cancel embedded products failed', [], $e, ['ref_id' => $travelQuote->uuid]);
            }
        }

        if ($hasPolicySentOrBookedStatusChange) {

            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Travel, 'quoteUID' => $travelQuote->uuid]);
            ExtendCustomerSubscriptionViaSQS::dispatch(
                $travelQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );

            try {
                event(new PrivateClientUpdatedEvent($travelQuote, QuoteTypeId::Travel));
            } catch (Exception $e) {
                LoggerService::warning(self::LOG_PRIVATE_CLIENT_UPDATED_FAILED, [
                    'uuid' => $travelQuote->uuid,
                    'quote_status_id' => $travelQuote->quote_status_id,
                ], exception: $e);
            }

            try {
                EmbeddedProductRepository::capturePayment($travelQuote->id, quoteTypeCode::Travel);
            } catch (Exception $e) {
                LoggerService::error('TravelQuoteObserver - capture embedded products failed', [], $e, ['ref_id' => $travelQuote->uuid]);
            }
        }

        // For debugging
        LoggerService::info('TravelQuoteObserver - reached inside policy booked check', [
            'uuid' => $travelQuote->uuid,
            'hasPolicyBookedStatusChange' => $hasPolicyBookedStatusChange,
        ]);

        if ($hasPolicyBookedStatusChange) {
            try {
                QuotePolicyBooked::dispatch($travelQuote->uuid, QuoteTypeId::Travel);
            } catch (Exception $e) {
                LoggerService::error('TravelQuoteObserver - dispatch QuotePolicyBooked event failed', [], $e, ['ref_id' => $travelQuote->uuid]);
            }
        }

        if ($quoteStatusChanged && $travelQuote->quote_status_id === QuoteStatusEnum::PolicyIssued) {
            SendPolicyIssueWhatsappMessageJob::dispatch($travelQuote->uuid, QuoteTypes::TRAVEL->id())->onQueue('insly');
            $payment = $travelQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($travelQuote, $payment, QuoteTypes::TRAVEL->value);
            try {
                event(new PrivateClientUpdatedEvent($travelQuote, QuoteTypeId::Travel));
            } catch (Exception $e) {
                LoggerService::warning(self::LOG_PRIVATE_CLIENT_UPDATED_FAILED, [
                    'uuid' => $travelQuote->uuid,
                    'quote_status_id' => $travelQuote->quote_status_id,
                ], exception: $e);
            }
        }
    }

    protected function shouldStopSIC(array $dirty, TravelQuote $travelQuote): bool
    {
        // Stop SIC follow-up emails based on lead status or payment status
        static $stopSICStatuses = [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
        ];

        static $stopSICPaymentStatuses = [
            PaymentStatusEnum::PAID,
            PaymentStatusEnum::CANCELLED,
        ];

        return (isset($dirty['quote_status_id']) && in_array($travelQuote->quote_status_id, $stopSICStatuses, true)) || (isset($dirty['payment_status_id']) && in_array($travelQuote->payment_status_id, $stopSICPaymentStatuses, true));
    }
}
