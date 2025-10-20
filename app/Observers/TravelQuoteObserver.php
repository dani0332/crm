<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Events\PrivateClientUpdatedEvent;
use App\Events\TravelQuoteAdvisorUpdated;
use App\Jobs\Audit\LogAllocation;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\ExtendCustomerSubscriptionViaSQS;
use App\Jobs\SendFailedPaymentEmailJob;
use App\Jobs\SendPolicyIssueWhatsappMessageJob;
use App\Models\TravelQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\PaymentRepository;
use App\Services\Logger\LoggerService;
use App\Services\SIBService;
use App\Traits\PersonalQuoteSyncTrait;
use Exception;
use Illuminate\Support\Facades\Log;

class TravelQuoteObserver
{
    use PersonalQuoteSyncTrait;

    public function updating(TravelQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
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

        foreach ($dirty as $attribute => $value) {
            $changes[$attribute] = [
                'old' => $travelQuote->getOriginal($attribute),
                'new' => $value,
            ];
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

        if (
            isset($dirty['quote_status_id']) &&
            $travelQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            TravelQuote::withoutEvents(function () use ($travelQuote) {
                $travelQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $travelQuote->transaction_approved_at];
        }

        $this->syncQuote($travelQuote, $dirty);

        if (isset($dirty['quote_status_id']) && $travelQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            try {
                $this->updatePersonalQuote($travelQuote->uuid, QuoteTypeId::Travel, $dirty);
            } catch (Exception $e) {
                Log::error('TravelQuoteObserver - update personal quote failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $travelQuote->uuid,
                ]);
            }
        }

        if (isset($dirty['quote_status_id']) && $travelQuote->quote_status_id === QuoteStatusEnum::PolicyCancelled) {
            try {
                EmbeddedProductRepository::cancelEmbeddedProducts($travelQuote->id, quoteTypeCode::Travel);
            } catch (Exception $e) {
                LoggerService::error('TravelQuoteObserver - cancel embedded products failed', [], $e, ['ref_id' => $travelQuote->uuid]);
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            in_array($travelQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Travel, 'quoteUID' => $travelQuote->uuid]);
            ExtendCustomerSubscriptionViaSQS::dispatch(
                $travelQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
            event(new PrivateClientUpdatedEvent($travelQuote, QuoteTypeId::Travel));

            try {
                EmbeddedProductRepository::capturePayment($travelQuote->id, quoteTypeCode::Travel);
            } catch (Exception $e) {
                LoggerService::error('TravelQuoteObserver - capture embedded products failed', [], $e, ['ref_id' => $travelQuote->uuid]);
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            $travelQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            SendPolicyIssueWhatsappMessageJob::dispatch($travelQuote->uuid, QuoteTypes::TRAVEL->id())->onQueue('insly');
            $payment = $travelQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($travelQuote, $payment, QuoteTypes::TRAVEL->value);
            event(new PrivateClientUpdatedEvent($travelQuote, QuoteTypeId::Travel));
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
