<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\BusinessQuote;
use App\Models\User;

final class GroupMedicalEcommerceJourneyLinkService
{
    /**
     * Lead statuses where the advisor may no longer copy the customer ecommerce journey link
     * (Transaction Approved and downstream policy / booking states).
     *
     * @var list<int>
     */
    private const ADVISOR_COPY_DISABLED_STATUS_IDS = [
        QuoteStatusEnum::TransactionApproved,
        QuoteStatusEnum::PolicyDocumentsPending,
        QuoteStatusEnum::PolicyIssued,
        QuoteStatusEnum::PolicySentToCustomer,
        QuoteStatusEnum::PolicyBooked,
        QuoteStatusEnum::PolicyInvoiced,
        QuoteStatusEnum::CancellationPending,
        QuoteStatusEnum::PolicyCancelled,
        QuoteStatusEnum::PolicyCancelledReissued,
        QuoteStatusEnum::POLICY_BOOKING_QUEUED,
        QuoteStatusEnum::POLICY_BOOKING_FAILED,
        QuoteStatusEnum::PolicyPending,
        QuoteStatusEnum::EarlyRenewal,
    ];

    public function isAdvisorCopyEnabled(BusinessQuote $quote): bool
    {
        return ! in_array((int) $quote->quote_status_id, self::ADVISOR_COPY_DISABLED_STATUS_IDS, true);
    }

    public function buildCustomerJourneyUrl(BusinessQuote $quote): ?string
    {

        if (in_array($quote->source, [LeadSourceEnum::CALL_DESK_WHATSAPP, LeadSourceEnum::CALL_DESK])) {

            $base = config('constants.ECOM_GROUP_MEDICAL_INSURANCE_QUOTE_URL_FIRST_STEP');
            if (! is_string($base) || trim($base) === '') {
                return null;
            }

            return rtrim($base, '/')."/?{$quote->uuid}&resume=true";
        } else {

            $base = config('constants.ECOM_GROUP_MEDICAL_INSURANCE_QUOTE_URL');
            if (! is_string($base) || trim($base) === '') {
                return null;
            }

            $resource = $quote->quote_status_id == QuoteStatusEnum::QualificationPending ? 'documents' : 'plan-type';

            return rtrim($base, '/')."/{$quote->uuid}/{$resource}/?resume=true";
        }
    }

    public function recordAdvisorCopyLinkAudit(BusinessQuote $quote, User $user): void
    {
        $quote->audits()->create([
            'user_type' => $user::class,
            'user_id' => $user->id,
            'event' => 'gm_ecommerce_copy_link',
            'old_values' => [],
            'new_values' => [
                'action' => 'gm_ecommerce_journey_link_copied',
                'quote_uuid' => $quote->uuid,
                'quote_code' => $quote->code,
            ],
        ]);
    }
}
