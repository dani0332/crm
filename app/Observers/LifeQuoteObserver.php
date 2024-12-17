<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Models\LifeQuote;
use App\Repositories\PaymentRepository;
use App\Traits\PersonalQuoteSyncTrait;
use Exception;
use Illuminate\Support\Facades\Log;

class LifeQuoteObserver
{
    use PersonalQuoteSyncTrait;

    public function updating(LifeQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    /**
     * Handle the LifeQuote "updated" event.
     *
     * - Any changes that adds business logic should be enclosed in try-catch block or executed in queue.
     */
    public function updated(LifeQuote $lifeQuote): void
    {
        $dirty = $lifeQuote->getDirty();
        if (
            isset($dirty['quote_status_id']) &&
            $lifeQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            LifeQuote::withoutEvents(function () use ($lifeQuote) {
                $lifeQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $lifeQuote->transaction_approved_at];
        }

        $this->syncQuote($lifeQuote, $dirty);

        if (isset($dirty['quote_status_id']) && $lifeQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            try {
                $this->updatePersonalQuote($lifeQuote->uuid, QuoteTypeId::Life, $dirty);
            } catch (Exception $e) {
                Log::error('LifeQuoteObserver - update personal quote failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $lifeQuote->uuid,
                ]);
            }

        }

        if (
            isset($dirty['quote_status_id']) &&
            in_array($lifeQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Life, 'quoteUID' => $lifeQuote->uuid]);
            MAWelcomeJob::dispatch(
                $lifeQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }

        if (
            isset($dirty['quote_status_id']) &&
            $lifeQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            $payment = $lifeQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($lifeQuote, $payment, QuoteTypes::LIFE->value);

        }
    }
}
