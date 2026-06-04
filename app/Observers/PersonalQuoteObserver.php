<?php

namespace App\Observers;

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Events\Device\DevicePaymentAuthorised;
use App\Jobs\Audit\LogAllocation;
use App\Models\PersonalQuote;
use App\Observers\Traits\Observable;
use App\Observers\Traits\PersonalQuoteObservable;
use App\Services\QuoteStatusLogService;
use App\Traits\GenericQueriesAllLobs;

class PersonalQuoteObserver
{
    use GenericQueriesAllLobs, Observable, PersonalQuoteObservable;

    public function updating(PersonalQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id')) {
            app(QuoteStatusLogService::class)->createQuoteStatusLog(
                (int) $quote->quote_type_id,
                $quote,
                $quote->getOriginal('quote_status_id'),
            );

            if (! $quote->isDirty('quote_status_date')) {
                $quote->quote_status_date = now();
            }
        }
    }

    /**
     * Handle the "updated" event.
     *
     * - Any changes that adds business logic should be enclosed in try-catch block or executed in queue.
     */
    public function updated(PersonalQuote $personalQuote): void
    {
        $this->printChangeLog($personalQuote);

        if ($personalQuote->isDirty('advisor_id') && ! empty($personalQuote->advisor_id)) {
            LogAllocation::dispatch($personalQuote);

            $oldAdvisorId = $personalQuote->getOriginal('advisor_id') ?? null;

            $this->handleAdvisorChange($personalQuote, $oldAdvisorId);
        }

        if ($personalQuote->isDirty('quote_status_id')) {
            $this->handleQuoteStatusChange($personalQuote);
        }

        $this->sendFTCEmailOnPaymentAuthorised($personalQuote);
    }

    /**
     * Handle the FTC email dispatch on payment authorised.
     */
    public function sendFTCEmailOnPaymentAuthorised(PersonalQuote $quote): void
    {
        // Only handle Device quotes
        if ($quote->quote_type_id !== QuoteTypeId::Device) {
            return;
        }

        // Check if payment status changed to AUTHORISED
        if ($quote->wasChanged('payment_status_id') &&
            $quote->payment_status_id === PaymentStatusEnum::AUTHORISED) {

            DevicePaymentAuthorised::dispatch($quote);
        }
    }
}
