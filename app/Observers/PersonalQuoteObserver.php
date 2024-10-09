<?php

namespace App\Observers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Events\BikeQuoteAdvisorUpdated;
use App\Jobs\MAWelcomeJob;
use App\Models\PersonalQuote;
use App\Repositories\PaymentRepository;
use Exception;
use Illuminate\Support\Facades\Log;

class PersonalQuoteObserver
{
    public function updating(PersonalQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    public function updated(PersonalQuote $personalQuote): void
    {
        if (
            $personalQuote->isDirty('quote_status_id') &&
            $personalQuote->quote_status_id === QuoteStatusEnum::TransactionApproved &&
            checkPersonalQuotes($personalQuote->quoteType?->code)
        ) {
            MAWelcomeJob::dispatchIf(
                isMyAlfredCampaignEnabled(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN)) && $personalQuote->customer,
                $personalQuote->customer?->first_name,
                $personalQuote->customer?->last_name,
                $personalQuote->customer?->email,
                $personalQuote->customer?->mobile_no,
                'CUSTOMER_UPDATE',
                'customer-update-myalfred-we'
            );

            PersonalQuote::withoutEvents(function () use ($personalQuote) {
                $personalQuote->update(['transaction_approved_at' => now()]);
            });
        }

        // Bike Case in Lead Allocation Process- Bike Quote Advisor Change
        if (
            $personalQuote->quote_type_id === 6
        ) {
            $changes = [];

            foreach ($personalQuote->getDirty() as $attribute => $value) {
                if ($personalQuote->isDirty($attribute)) {
                    $changes[$attribute] = [
                        'old' => $personalQuote->getOriginal($attribute),
                        'new' => $value,
                    ];
                }
            }

            try {
                // handle the bikeQuote advisor_id change event in lead allocation
                if (
                    $personalQuote->isDirty('advisor_id') &&
                    $personalQuote->advisor_id !== null &&
                    $personalQuote->advisor_id !== 0
                ) {
                    $oldAdvisorId = $changes['advisor_id']['old'];
                    event(new BikeQuoteAdvisorUpdated($personalQuote, $oldAdvisorId));
                }
            } catch (Exception $e) {
                Log::error('PersonalQuoteObserver Error: '.$e->getMessage());
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            $personalQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            $payment = $personalQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($payment, QuoteTypes::PERSONAL->value);

        }
    }
}
