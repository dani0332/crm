<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Events\CarQuoteAdvisorUpdated;
use App\Facades\Ken;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\MACRM\CancelCourierQuoteOnMACRM;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Jobs\MAWelcomeJob;
use App\Models\CarQuote;
use App\Models\CustomerAddress;
use App\Repositories\PaymentRepository;
use App\Traits\PersonalQuoteSyncTrait;

class CarQuoteObserver
{
    use PersonalQuoteSyncTrait;

    public function updating(CarQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    public function updated(CarQuote $lead)
    {
        $dirty = $lead->getDirty();
        $changes = [];

        foreach ($dirty as $attribute => $value) {
            $changes[$attribute] = [
                'old' => $lead->getOriginal($attribute),
                'new' => $value,
            ];
        }

        if (isset($dirty['advisor_id'])) {
            $lead->markLeadAllocationPassed();
            $oldAdvisorId = $changes['advisor_id']['old'];
            event(new CarQuoteAdvisorUpdated($lead, $oldAdvisorId));
        }

        if (isset($dirty['quote_status_id'])) {
            if ($lead->quote_status_id === QuoteStatusEnum::TransactionApproved) {
                CarQuote::withoutEvents(function () use ($lead) {
                    $lead->update([
                        'transaction_approved_at' => now(),
                        'quote_status_date' => now(),
                    ]);
                });
                $dirty = [...$dirty, 'transaction_approved_at' => $lead->transaction_approved_at];
            }

            if ($lead->quote_status_id === QuoteStatusEnum::PolicyIssued) {
                SyncCourierQuoteWithMacrm::dispatch($lead, QuoteTypeId::Car);
            }

            if (in_array($lead->quote_status_id, [QuoteStatusEnum::PolicyCancelled])) {
                CancelCourierQuoteOnMACRM::dispatch($lead, QuoteTypeId::Car);
            }
        }

        $this->syncQuote($lead, $dirty);

        if (isset($dirty['quote_status_id']) && $lead->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            $this->updatePersonalQuote($lead->uuid, QuoteTypeId::Car, $dirty);
        }

        if (
            isset($dirty['quote_status_id']) &&
            in_array($lead->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Car, 'quoteUID' => $lead->uuid]);
            MAWelcomeJob::dispatch(
                $lead->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }
        if (
            isset($dirty['quote_status_id']) &&
            $lead->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            $payment = $lead->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($lead, $payment, QuoteTypes::CAR->value);

            // check if policy is issued and address is not entered then call KEN API
            $this->sendAddressReminderOnPolicyIssue($lead);
        }
    }

    public function sendAddressReminderOnPolicyIssue(CarQuote $lead)
    {
        $address = CustomerAddress::where('quote_uuid', $lead->uuid)->first();
        if (! $address) {
            // send address reminder to customer if address is not entered
            if ($lead->embeddedTransactions()->exists()) {
                $courierEmbeddedTransaction = $lead->embeddedTransactions
                    ->filter(function ($transaction) {
                        return $transaction->product?->embeddedProduct?->short_code === 'COU';
                    });
            }

            if (
                $courierEmbeddedTransaction->isNotEmpty()
            ) {
                info('Triggering Bird Courier Flow for policy reminder for lead : ' . $lead->uuid);
                $embeddedTransactionRefId = $courierEmbeddedTransaction->first()->code;
                $payload = [
                    'quoteUID' => $lead->uuid,
                    'quoteTypeId' => (int) QuoteTypes::CAR->id(),
                    'actionType' => 'POLICY_ISSUED',
                    'refId' => $embeddedTransactionRefId,
                ];

                Ken::request('/trigger-bird-courier-flow', 'post', $payload);
            }
        }
    }
}
