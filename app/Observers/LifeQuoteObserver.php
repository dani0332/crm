<?php

namespace App\Observers;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Events\PrivateClientUpdatedEvent;
use App\Events\QuotePolicyBooked;
use App\Jobs\Audit\LogAllocation;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\ExtendCustomerSubscriptionViaSQS;
use App\Jobs\SendPolicyIssueWhatsappMessageJob;
use App\Models\LifeQuote;
use App\Repositories\PaymentRepository;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use Exception;

class LifeQuoteObserver
{
    private const LOG_PRIVATE_CLIENT_UPDATED_FAILED = 'LifeQuoteObserver - dispatch PrivateClientUpdatedEvent failed';

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
        if (isset($dirty['advisor_id'])) {
            LogAllocation::dispatch($lifeQuote, QuoteTypes::LIFE);

            if (! $lifeQuote->isSuppressIntroEmail() && $lifeQuote->source != LeadSourceEnum::IMCRM) {

                $oldAdvisorId = $lifeQuote->getOriginal('advisor_id');
                LoggerService::info(self::class." - Advisor ID updated - Old Advisor ID: {$oldAdvisorId} | New Advisor ID: {$lifeQuote->advisor_id} ");

                $emailType = empty($oldAdvisorId) ? 'introductory' : 'reassignment';
                LoggerService::info(self::class." Sending {$emailType} email to customer for life quote {$lifeQuote->uuid} ");
                app(SendEmailCustomerService::class)->sendIntroAndReassignEmail($lifeQuote, QuoteTypes::LIFE->value, $oldAdvisorId);
                LoggerService::info(self::class." | {$emailType} email sent to customer for life quote {$lifeQuote->uuid} ");

            } else {
                LoggerService::info("LifeQuoteObserver - lead source: {$lifeQuote->source} |  Advisor ID: {$lifeQuote->advisor_id}  Quote Status: {$lifeQuote->quote_status_id} ");
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            in_array($lifeQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Life, 'quoteUID' => $lifeQuote->uuid]);
            ExtendCustomerSubscriptionViaSQS::dispatch(
                $lifeQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }

        if (
            isset($dirty['quote_status_id']) &&
            $lifeQuote->quote_status_id === QuoteStatusEnum::PolicyBooked
        ) {
            try {
                QuotePolicyBooked::dispatch($lifeQuote->uuid, QuoteTypeId::Life, leadSource: $lifeQuote->source);
            } catch (Exception $e) {
                LoggerService::error('LifeQuoteObserver - dispatch QuotePolicyBooked event failed', [], $e, ['ref_id' => $lifeQuote->uuid]);
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            $lifeQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            LoggerService::info(self::class.' fn:'.__FUNCTION__.' - Quote Code '.$lifeQuote->code.' Policy Issued ');
            SendPolicyIssueWhatsappMessageJob::dispatch($lifeQuote->uuid, QuoteTypes::LIFE->id())->onQueue('insly');
            $payment = $lifeQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($lifeQuote, $payment, QuoteTypes::LIFE->value);

            try {
                event(new PrivateClientUpdatedEvent($lifeQuote, QuoteTypeId::Life));
            } catch (Exception $e) {
                LoggerService::warning(self::LOG_PRIVATE_CLIENT_UPDATED_FAILED, [
                    'uuid' => $lifeQuote->uuid,
                    'quote_status_id' => $lifeQuote->quote_status_id,
                ], exception: $e);
            }
        }
    }
}
