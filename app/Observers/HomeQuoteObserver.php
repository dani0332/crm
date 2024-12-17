<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MAWelcomeJob;
use App\Models\ApplicationStorage;
use App\Models\HomeQuote;
use App\Repositories\PaymentRepository;
use App\Services\EmailServices\HomeEmailService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteSyncTrait;
use Exception;
use Illuminate\Support\Facades\Log;

class HomeQuoteObserver
{
    use GenericQueriesAllLobs, PersonalQuoteSyncTrait;

    public function updating(HomeQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    /**
     * Handle the HomeQuote "updated" event.
     *
     * - Any changes that adds business logic should be enclosed in try-catch block or executed in queue.
     */
    public function updated(HomeQuote $homeQuote): void
    {
        $dirty = $homeQuote->getDirty();

        if (isset($dirty['advisor_id'])) {
            $homeOCBSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::HOME_OCB_AUTOMATED_FOLLOWUPS_SWITCH)->first();
            if ($homeOCBSwitch && $homeOCBSwitch->value == 1) {
                app(HomeEmailService::class)->sendHomeOCBIntroEmail($homeQuote);
                info("HomeQuoteObserver - Home OCB Automated Followups Switch is on - Ref ID: {$homeQuote->uuid} | Time: ".now());
            } else {
                info("HomeQuoteObserver - Home OCB Automated Followups Switch is off - Ref ID: {$homeQuote->uuid} | Time: ".now());
            }

        }
        if (
            isset($dirty['quote_status_id']) &&
            $homeQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            HomeQuote::withoutEvents(function () use ($homeQuote) {
                $homeQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $homeQuote->transaction_approved_at];
        }

        if (isset($dirty['quote_status_id']) && $this->removeStaleFromLead($homeQuote->quote_status_id)) {
            HomeQuote::withoutEvents(function () use ($homeQuote) {
                $homeQuote->update(['stale_at' => null]);
            });
            $dirty = [...$dirty, 'stale_at' => $homeQuote->stale_at];
        }

        $this->syncQuote($homeQuote, $dirty);

        if (isset($dirty['quote_status_id']) && $homeQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            try {
                $this->updatePersonalQuote($homeQuote->uuid, QuoteTypeId::Home, $dirty);
            } catch (Exception $e) {
                Log::error('HomeQuoteObserver - update personal quote failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $homeQuote->uuid,
                ]);
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            in_array($homeQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Home, 'quoteUID' => $homeQuote->uuid]);
            MAWelcomeJob::dispatch(
                $homeQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }

        if (
            isset($dirty['quote_status_id']) &&
            $homeQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            $payment = $homeQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($homeQuote, $payment, QuoteTypes::HOME->value);

        }
    }
}
