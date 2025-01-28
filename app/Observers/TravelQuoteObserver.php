<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Events\TravelQuoteAdvisorUpdated;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Models\TravelQuote;
use App\Repositories\PaymentRepository;
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

        if (isset($dirty['advisor_id'])) {
            try {
                $travelQuote->markLeadAllocationPassed();

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

        if (
            isset($dirty['quote_status_id']) &&
            in_array($travelQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Travel, 'quoteUID' => $travelQuote->uuid]);
            MAWelcomeJob::dispatch(
                $travelQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }

        if (
            isset($dirty['quote_status_id']) &&
            $travelQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            $payment = $travelQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($travelQuote, $payment, QuoteTypes::TRAVEL->value);

        }
    }
}
