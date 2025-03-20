<?php

namespace App\Observers;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Jobs\MAWelcomeJob;
use App\Models\BusinessQuote;
use App\Repositories\PaymentRepository;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteSyncTrait;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\SendEmailCustomerService;
use App\Enums\LeadSourceEnum;
use App\Enums\BusinessTypeOfInsuranceIdEnum;

class BusinessQuoteObserver
{
    use GenericQueriesAllLobs, PersonalQuoteSyncTrait;

    public function updating(BusinessQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    /**
     * Handle the BusinessQuote "updated" event.
     *
     * - Any changes that adds business logic should be enclosed in try-catch block or executed in queue.
     */
    public function updated(BusinessQuote $businessQuote): void
    {
        $dirty = $businessQuote->getDirty();
        if (
            isset($dirty['quote_status_id']) &&
            $businessQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            BusinessQuote::withoutEvents(function () use ($businessQuote) {
                $businessQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $businessQuote->transaction_approved_at];
        }

            if ($businessQuote->source != LeadSourceEnum::IMCRM  && $businessQuote->business_type_of_insurance_id = BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL) {
                info(self::class." -  business_type_of_insurance ID: {$businessQuote->business_type_of_insurance_id} | Time: ".now());
                $oldAdvisorId = $businessQuote->getOriginal('advisor_id');
                info(self::class." - Advisor ID updated - Old Advisor ID: {$oldAdvisorId} | New Advisor ID: {$businessQuote->advisor_id} | Time: ".now());

                $emailType = empty($oldAdvisorId) ? 'introductory' : 'reassignment';
                info(self::class." Sending {$emailType} email to customer for life quote {$businessQuote->uuid} | Time: ".now());
                app(SendEmailCustomerService::class)->sendIntroAndReassignEmail($businessQuote, QuoteTypes::GROUP_MEDICAL->value, $oldAdvisorId);
                info(self::class." | {$emailType} email sent to customer for life quote {$businessQuote->uuid} | Time: ".now());

            } else {
                info(self::class." - lead source: {$businessQuote->source} |  Advisor ID: {$businessQuote->advisor_id} | Time: ".now());
            }

        if (isset($dirty['quote_status_id']) && $this->removeStaleFromLead($businessQuote->quote_status_id)) {
            BusinessQuote::withoutEvents(function () use ($businessQuote) {
                $businessQuote->update(['stale_at' => null]);
            });
            $dirty = [...$dirty, 'stale_at' => $businessQuote->stale_at];
        }

        $this->syncQuote($businessQuote, $dirty);

        if (isset($dirty['quote_status_id']) && $businessQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            try {
                $this->updatePersonalQuote($businessQuote->uuid, QuoteTypeId::Business, $dirty);
            } catch (Exception $e) {
                Log::error('BusinessQuoteObserver - update personal quote failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $businessQuote->uuid,
                ]);
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            in_array($businessQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            MAWelcomeJob::dispatch(
                $businessQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }

        if (
            isset($dirty['quote_status_id']) &&
            $businessQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            $payment = $businessQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($businessQuote, $payment, QuoteTypes::BUSINESS->value);

        }
    }
}
