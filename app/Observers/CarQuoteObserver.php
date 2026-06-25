<?php

namespace App\Observers;

use App\Enums\BranchEnum;
use App\Enums\CarRegistrationType;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Events\CarQuoteAdvisorUpdated;
use App\Events\LeadStatusUpdated;
use App\Events\PrivateClientUpdatedEvent;
use App\Events\QuotePolicyBooked;
use App\Jobs\Audit\LogAllocation;
use App\Jobs\CarMissingDocReminderJob;
use App\Jobs\CourtesyEmailJob;
use App\Jobs\EP\RetargetEpReminderJob;
use App\Jobs\ExtendCustomerSubscriptionViaSQS;
use App\Jobs\SendFailedPaymentEmailJob;
use App\Jobs\SendPolicyIssueWhatsappMessageJob;
use App\Models\CarQuote;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\PaymentRepository;
use App\Services\BranchAssignmentService;
use App\Services\CarQuoteService;
use App\Services\EmailServices\CarEmailService;
use App\Services\Logger\LoggerService;
use App\Services\PartnerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\QuoteStatusLogService;
use App\Traits\PersonalQuoteSyncTrait;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class CarQuoteObserver
{
    use PersonalQuoteSyncTrait;

    private const LOG_LEAD_STATUS_UPDATED_FAILED = 'CarQuoteObserver - dispatch LeadStatusUpdated event failed';
    private const LOG_PRIVATE_CLIENT_UPDATED_FAILED = 'CarQuoteObserver - dispatch PrivateClientUpdatedEvent failed';

    public function updating(CarQuote $quote): void
    {
        if ($quote->isDirty('quote_status_id')) {
            LoggerService::info('CarQuoteObserver - updating event', [
                'uuid' => $quote->uuid,
                'old_quote_status_id' => $quote->getOriginal('quote_status_id'),
                'new_quote_status_id' => $quote->quote_status_id,
            ]);

            app(QuoteStatusLogService::class)->createQuoteStatusLog(
                QuoteTypeId::Car,
                $quote,
                $quote->getOriginal('quote_status_id'),
            );

            if (! $quote->isDirty('quote_status_date')) {
                $quote->quote_status_date = now();
            }
        }
    }

    private function checkIfAnythingDirty(array $dirty, array $exclude = []): bool
    {
        foreach ($dirty as $attribute => $value) {
            if (! in_array($attribute, $exclude)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle the "updated" event.
     *
     * - Any changes that adds business logic should be enclosed in try-catch block or executed in queue.
     */
    public function updated(CarQuote $lead)
    {
        $dirty = $lead->getChanges();

        LoggerService::info('CarQuoteObserver - updated event', [
            'uuid' => $lead->uuid,
            'old_quote_status_id' => $lead->getOriginal('quote_status_id'),
            'new_quote_status_id' => $lead->quote_status_id,
            'dirty' => $dirty,
        ]);
        $changes = [];

        if (Route::currentRouteName() == 'car.update' && $this->checkIfAnythingDirty($dirty, ['first_name', 'last_name', 'email', 'mobile_no', 'updated_at', 'is_quote_locked', 'quote_updated_at', 'advisor_id'])) {
            LoggerService::info('CarQuoteObserver - Going to get quote plans again because of dirty fields with latest rating', ['uuid' => $lead->uuid, 'dirty' => $dirty]);
            app(CarQuoteService::class)->getQuotePlans($lead->uuid, getLatestRating: true);
        }

        foreach ($dirty as $attribute => $value) {
            $changes[$attribute] = [
                'old' => $lead->getOriginal($attribute),
                'new' => $value,
            ];
        }

        if (isset($dirty['advisor_id'])) {
            try {
                $lead->markLeadAllocationPassed();
                $oldAdvisorId = $changes['advisor_id']['old'];

                LogAllocation::dispatch($lead, QuoteTypes::CAR);
                SendFailedPaymentEmailJob::dispatch($lead->uuid, QuoteTypes::CAR);

                event(new CarQuoteAdvisorUpdated($lead, $oldAdvisorId));
            } catch (Exception $e) {
                Log::error('CarQuoteObserver - handle car update advisor failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $lead->uuid,
                ]);
            }
        }

        if (isset($dirty['quote_status_id'])) {
            if ($lead->quote_status_id === QuoteStatusEnum::Quoted && $lead->registration_type === CarRegistrationType::COMPANY && $lead->source === LeadSourceEnum::RENEWAL_UPLOAD) {
                app(CarEmailService::class)->sendFollowUpEmailForCQF($lead);
            }
            if ($lead->quote_status_id === QuoteStatusEnum::TransactionApproved) {
                CarQuote::withoutEvents(function () use ($lead) {
                    $lead->update([
                        'transaction_approved_at' => now(),
                        'quote_status_date' => now(),
                    ]);
                });
                $dirty = [...$dirty, 'transaction_approved_at' => $lead->transaction_approved_at];
            }
        }
        LoggerService::info('CarQuoteObserver - reached before policy booked check', [
            'uuid' => $lead->uuid,
            'dirty' => $dirty,
        ]);

        if (isset($dirty['quote_status_id']) && $lead->quote_status_id === QuoteStatusEnum::PolicyBooked) {
            LoggerService::info('CarQuoteObserver - reached inside policy booked check', [
                'uuid' => $lead->uuid,
                'dirty' => $dirty,
            ]);
            try {
                $this->updatePersonalQuote($lead->uuid, QuoteTypeId::Car, $dirty);
            } catch (Exception $e) {
                Log::error('CarQuoteObserver - update personal quote failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $lead->uuid,
                ]);
            }

            RetargetEpReminderJob::dispatch($lead->uuid, QuoteTypeId::Car);

            try {
                app(PartnerService::class)->sendPolicyDocumentsToPartner($lead->uuid, QuoteTypes::CAR);
            } catch (Exception $e) {
                LoggerService::error('CarQuoteObserver - send partner policy documents failed', [
                    'uuid' => $lead->uuid,
                ], exception: $e);
            }

            try {
                app(BranchAssignmentService::class)->saveBranchOverride($lead, QuoteTypeId::Car);
                CarQuote::withoutEvents(function () use ($lead, &$dirty) {

                    $shouldValidateBranch = app(PolicyIssuanceService::class)->shouldValidateBranch($lead, QuoteTypes::CAR->value);
                    $branch_id = null;
                    if ($shouldValidateBranch) {
                        $branch = app(BranchAssignmentService::class)->getBranch($lead?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Car);
                        $branch_id = $branch?->id;
                    } else {
                        $branch_id = BranchEnum::DUBAI->value;
                    }

                    $lead->update([
                        'branch_id' => $branch_id,
                    ]);
                    $dirty = [...$dirty, 'branch_id' => $branch_id];
                });
            } catch (Exception $e) {
                LoggerService::error('CarQuoteObserver - save branch data failed', [
                    'uuid' => $lead->uuid,
                ], exception: $e);
            }
        }

        $this->syncQuote($lead, $dirty);

        if (isset($dirty['quote_status_id']) && $lead->quote_status_id === QuoteStatusEnum::PolicyCancelled) {
            try {
                LeadStatusUpdated::dispatch(QuoteTypes::CAR, $lead->uuid);
            } catch (Exception $e) {
                LoggerService::warning(self::LOG_LEAD_STATUS_UPDATED_FAILED, [
                    'uuid' => $lead->uuid,
                    'quote_status_id' => $lead->quote_status_id,
                ], exception: $e);
            }

            try {
                EmbeddedProductRepository::cancelEmbeddedProducts($lead->id, quoteTypeCode::Car);
            } catch (Exception $e) {
                Log::error('CarQuoteObserver - cancel embedded products failed', [
                    'error' => $e->getMessage(),
                    'uuid' => $lead->uuid,
                ]);
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            in_array($lead->quote_status_id, [QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked])
        ) {
            try {
                LeadStatusUpdated::dispatch(QuoteTypes::CAR, $lead->uuid);
            } catch (Exception $e) {
                LoggerService::warning(self::LOG_LEAD_STATUS_UPDATED_FAILED, [
                    'uuid' => $lead->uuid,
                    'quote_status_id' => $lead->quote_status_id,
                ], exception: $e);
            }

            CourtesyEmailJob::dispatch(['quoteTypeId' => QuoteTypeId::Car, 'quoteUID' => $lead->uuid]);
            ExtendCustomerSubscriptionViaSQS::dispatch(
                $lead->customer,
                'LEAD_STATUS_UPDATE',
                'lead-status-update-myalfred-we'
            );

            if ($lead->quote_status_id == QuoteStatusEnum::PolicySentToCustomer) {
                try {
                    EmbeddedProductRepository::capturePayment($lead->id, quoteTypeCode::Car);
                } catch (Exception $e) {
                    Log::error('CarQuoteObserver - capture embedded products failed', [
                        'error' => $e->getMessage(),
                        'uuid' => $lead->uuid,
                    ]);
                }
            }
        }

        // For debugging
        LoggerService::info('CarQuoteObserver - reached inside policy booked check', [
            'uuid' => $lead->uuid,
            'dirty' => isset($dirty['quote_status_id']),
            'is_policy_booked' => $lead->quote_status_id === QuoteStatusEnum::PolicyBooked,
        ]);

        if (
            isset($dirty['quote_status_id']) &&
            $lead->quote_status_id === QuoteStatusEnum::PolicyBooked
        ) {
            try {
                QuotePolicyBooked::dispatch($lead->uuid, QuoteTypeId::Car);
            } catch (Exception $e) {
                LoggerService::error('CarQuoteObserver - dispatch QuotePolicyBooked event failed', [
                    'uuid' => $lead->uuid,
                ], exception: $e);
            }

            try {
                event(new PrivateClientUpdatedEvent($lead, QuoteTypeId::Car));
            } catch (Exception $e) {
                LoggerService::warning(self::LOG_PRIVATE_CLIENT_UPDATED_FAILED, [
                    'uuid' => $lead->uuid,
                    'quote_status_id' => $lead->quote_status_id,
                ], exception: $e);
            }
        }

        if (
            isset($dirty['quote_status_id']) &&
            $lead->quote_status_id === QuoteStatusEnum::PolicyIssued
        ) {
            SendPolicyIssueWhatsappMessageJob::dispatch($lead->uuid, QuoteTypes::CAR->id())->onQueue('insly');

            try {
                LeadStatusUpdated::dispatch(QuoteTypes::CAR, $lead->uuid);
            } catch (Exception $e) {
                LoggerService::warning(self::LOG_LEAD_STATUS_UPDATED_FAILED, [
                    'uuid' => $lead->uuid,
                    'quote_status_id' => $lead->quote_status_id,
                ], exception: $e);
            }

            $payment = $lead->payments()->mainLeadPayment()->first();
            (new PaymentRepository)->generateAndStoreBrokerInvoiceNumber($lead, $payment, QuoteTypes::CAR->value);
        }
        if (isset($dirty['quote_status_id']) && $lead->quote_status_id === QuoteStatusEnum::PaymentPending) {

            if ($lead->payment_status_id === PaymentStatusEnum::AUTHORISED) {
                CarMissingDocReminderJob::dispatch($lead->uuid)->delay(now()->addSeconds(15));
                LoggerService::info(self::class.' - dispatching CarMissingDocReminderJob', ['uuid' => $lead->uuid]);
            }

        }

        if (
            isset($dirty['car_make_id'])
            || isset($dirty['car_model_id'])
            || isset($dirty['registration_type'])
            || isset($dirty['vehicle_use'])
            || isset($dirty['is_modified'])
        ) {
            app(EmbeddedProductRepository::class)->syncCarQuoteEpEcb($lead, QuoteTypeId::Car);
        }
    }
}
