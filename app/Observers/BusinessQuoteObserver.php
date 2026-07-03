<?php

namespace App\Observers;

use App\Enums\BranchEnum;
use App\Enums\BusinessTypeOfInsuranceEnum;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Events\QuotePolicyBooked;
use App\Jobs\Audit\LogAllocation;
use App\Jobs\DispatchIlaAllocationJob;
use App\Jobs\DispatchPqaAllocationJob;
use App\Jobs\ExtendCustomerSubscriptionViaSQS;
use App\Jobs\SendPolicyIssueWhatsappMessageJob;
use App\Models\BusinessQuote;
use App\Repositories\PaymentRepository;
use App\Services\BranchAssignmentService;
use App\Services\BusinessQuoteService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\QuoteStatusLogService;
use App\Services\SendEmailCustomerService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteSyncTrait;
use Exception;
use Illuminate\Support\Facades\Log;

class BusinessQuoteObserver
{
    use GenericQueriesAllLobs, PersonalQuoteSyncTrait;

    public function updating(BusinessQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id')) {
            app(QuoteStatusLogService::class)->createQuoteStatusLog(
                QuoteTypeId::Business,
                $quote,
                $quote->getOriginal('quote_status_id'),
            );

            if (! $quote->isDirty('quote_status_date')) {
                $quote->quote_status_date = now();
            }
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

        $oldAdvisorId = $businessQuote->getOriginal('advisor_id') ?? null;

        if (isset($dirty['advisor_id'])) {
            $businessTypeInsurance = '';

            LogAllocation::dispatch($businessQuote, QuoteTypes::BUSINESS);

            switch ($businessQuote->business_type_of_insurance_id) {
                case BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL:
                    $businessTypeInsurance = QuoteTypes::GROUP_MEDICAL->value;
                    break;
                case BusinessTypeOfInsuranceIdEnum::PROPERTY:
                    $businessTypeInsurance = BusinessTypeOfInsuranceEnum::PROPERTY;
                    break;
                case BusinessTypeOfInsuranceIdEnum::SEVERAL_INSURANCES:
                    $businessTypeInsurance = BusinessTypeOfInsuranceEnum::SEVERAL_INSURANCES;
                    break;
                default:
                    $businessTypeInsurance = QuoteTypes::CORPLINE->value;
                    break;
            }
            if (! $businessQuote->isSuppressIntroEmail() && $businessQuote->source != LeadSourceEnum::IMCRM && ! empty($businessTypeInsurance)) {
                LoggerService::info(self::class." -  business_type_of_insurance ID: {$businessQuote->business_type_of_insurance_id} | Ref-ID: {$businessQuote->uuid} ");
                LoggerService::info(self::class." - Advisor ID updated - Old Advisor ID: {$oldAdvisorId} | New Advisor ID: {$businessQuote->advisor_id} | Ref-ID: {$businessQuote->uuid}  ");

                $emailType = empty($oldAdvisorId) ? 'introductory' : 'reassignment';
                LoggerService::info(self::class." Sending {$emailType} email to customer for  {$businessTypeInsurance} quote {$businessQuote->uuid} ");
                $shortenedBusinessType = app(BusinessQuoteService::class)->formatInsuranceName($businessQuote->businessTypeOfInsurance->code ?? '');
                app(SendEmailCustomerService::class)->sendIntroAndReassignEmail($businessQuote, $businessTypeInsurance, $oldAdvisorId, $shortenedBusinessType ?? []);
                LoggerService::info(self::class." | {$emailType} email sent to customer for {$businessTypeInsurance} quote {$businessQuote->uuid} ");

            } else {
                LoggerService::info(self::class.' introductory email not sent for quote ', [
                    'quote_status_id' => $businessQuote->quote_status_id,
                    'source' => $businessQuote->source,
                    'business_type_of_insurance_id' => $businessQuote->business_type_of_insurance_id,
                    'ref_id' => $businessQuote->uuid,
                ]);
            }

        }
        if (
            isset($dirty['quote_status_id']) &&
            $businessQuote->quote_status_id === QuoteStatusEnum::TransactionApproved
        ) {
            BusinessQuote::withoutEvents(function () use ($businessQuote) {
                $businessQuote->update(['transaction_approved_at' => now()]);
            });
            $dirty = [...$dirty, 'transaction_approved_at' => $businessQuote->transaction_approved_at];
        }

        if (isset($dirty['quote_status_id']) && $this->removeStaleFromLead($businessQuote->quote_status_id)) {
            BusinessQuote::withoutEvents(function () use ($businessQuote) {
                $businessQuote->update(['stale_at' => null]);
            });
            $dirty = [...$dirty, 'stale_at' => $businessQuote->stale_at];
        }

        if (isset($dirty['quote_status_id']) && $businessQuote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            try {
                $this->updatePersonalQuote($businessQuote->uuid, QuoteTypeId::Business, $dirty);
            } catch (Exception $e) {
                Log::error('BusinessQuoteObserver - update personal quote failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $businessQuote->uuid,
                ]);
            }

            try {
                // Determine the correct quote type based on business type of insurance
                $quoteTypeId = QuoteTypeId::Business;
                $emirateOfRegistrationId = null;
                if ($businessQuote->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL) {
                    $quoteTypeId = QuoteTypeId::GroupMedical;
                    $emirateOfRegistrationId = $businessQuote->emirate_of_registration_id ?? null;
                }

                app(BranchAssignmentService::class)->saveBranchOverride($businessQuote, $quoteTypeId);
                BusinessQuote::withoutEvents(function () use ($businessQuote, $quoteTypeId, $emirateOfRegistrationId, &$dirty) {

                    $shouldValidateBranch = true;
                    if ($quoteTypeId === QuoteTypeId::Business) {
                        $shouldValidateBranch = app(PolicyIssuanceService::class)->shouldValidateBranch($businessQuote, QuoteTypes::BUSINESS->value);
                    }

                    $branch_id = null;
                    if ($shouldValidateBranch) {
                        $branch = app(BranchAssignmentService::class)->getBranch($businessQuote?->advisor?->primaryBranch?->branch_id, $quoteTypeId, $emirateOfRegistrationId);
                        $branch_id = $branch?->id;
                    } else {
                        $branch_id = BranchEnum::DUBAI->value;
                    }

                    $businessQuote->update([
                        'branch_id' => $branch_id,
                    ]);
                    $dirty = [...$dirty, 'branch_id' => $branch_id];
                });
            } catch (Exception $e) {
                LoggerService::error('BusinessQuoteObserver - save branch data failed', [
                    'uuid' => $businessQuote->uuid,
                ], exception: $e);
            }
        }

        $this->syncQuote($businessQuote, $dirty);

        if (
            isset($dirty['quote_status_id']) &&
            in_array($businessQuote->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            ExtendCustomerSubscriptionViaSQS::dispatch(
                $businessQuote->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );
        }

        if (
            isset($dirty['quote_status_id']) &&
            $businessQuote->quote_status_id === QuoteStatusEnum::PolicyBooked
        ) {
            try {
                // Determine the correct quote type based on business type of insurance
                $quoteTypeId = QuoteTypeId::Business;
                if ($businessQuote->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL) {
                    $quoteTypeId = QuoteTypeId::GroupMedical;
                }
                QuotePolicyBooked::dispatch($businessQuote->uuid, $quoteTypeId, leadSource: $businessQuote->source);
            } catch (Exception $e) {
                LoggerService::error('BusinessQuoteObserver - dispatch QuotePolicyBooked event failed', [
                    'uuid' => $businessQuote->uuid,
                ], exception: $e);
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            $businessQuote->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            SendPolicyIssueWhatsappMessageJob::dispatch($businessQuote->uuid, QuoteTypes::BUSINESS->id())->onQueue('insly');
            $payment = $businessQuote->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($businessQuote, $payment, QuoteTypes::BUSINESS->value);

        }

        $isCorpline = (int) $businessQuote->business_type_of_insurance_id !== BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;

        if (
            $isCorpline &&
            isset($dirty['quote_status_id']) &&
            $businessQuote->quote_status_id === QuoteStatusEnum::QualificationPending &&
            $businessQuote->pq_advisor_id === null
        ) {
            try {
                DispatchPqaAllocationJob::dispatch($businessQuote->uuid, QuoteTypes::CORPLINE)->afterCommit();

                activity()
                    ->performedOn($businessQuote)
                    ->withProperties(['lead_status' => 'Qualification Pending'])
                    ->log('Lead status set to Qualification Pending. PQA allocation triggered.');
            } catch (Exception $e) {
                LoggerService::error('BusinessQuoteObserver - PQA allocation dispatch failed', [
                    'uuid' => $businessQuote->uuid,
                ], exception: $e);
            }
        }

        if (
            $isCorpline &&
            isset($dirty['quote_status_id']) &&
            $businessQuote->quote_status_id === QuoteStatusEnum::Qualified &&
            $businessQuote->advisor_id === null
        ) {
            try {
                DispatchIlaAllocationJob::dispatch($businessQuote->uuid, QuoteTypes::CORPLINE)->afterCommit();
            } catch (Exception $e) {
                LoggerService::error('BusinessQuoteObserver - ILA dispatch on Qualified failed', [
                    'uuid' => $businessQuote->uuid,
                ], exception: $e);
            }
        }
    }
}
