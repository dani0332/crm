<?php

declare(strict_types=1);

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\ClaimRequest;
use App\Services\BaseService;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Exception;

/**
 * Google Review Email Service
 *
 * Handles sending Google review emails to customers when their claims are marked as closed.
 * Uses the Bird integration for email delivery.
 */
class ClaimRequestEmailService extends BaseService
{
    /**
     * Send Google review email to customer
     *
     * @param  ClaimRequest  $claimRequest  The claim request that was closed
     * @return int|null HTTP status code or null if failed
     */
    public function sendGoogleReviewEmail(ClaimRequest $claimRequest): ?int
    {
        try {
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Sending Google review email - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'customer_email' => $claimRequest->email,
                'claim_status_id' => $claimRequest->claim_status_id,
            ]);

            // Build email data
            $emailData = $this->buildGoogleReviewEmailData($claimRequest);

            // Get Bird webhook URL from application storage
            $googleReviewEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::GOOGLE_REVIEW_EMAIL)->first();

            if (! $googleReviewEvent) {
                LoggerService::warning(self::class.'::'.__FUNCTION__.' - Google review email workflow key not found - Claim UUID: '.$claimRequest->uuid, [
                    'claim_request_id' => $claimRequest->id,
                    'claim_uuid' => $claimRequest->uuid,
                ]);

                return null;
            }

            // Trigger Bird webhook
            $response = app(BirdService::class)->triggerWebHookRequest($googleReviewEvent->value, $emailData);

            LoggerService::info(self::class.'::'.__FUNCTION__.' - Google review email workflow triggered - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'response_status' => $response->status_code,
                'time' => now(),
            ]);

            return $response->status_code;

        } catch (Exception $e) {
            LoggerService::error(self::class.'::'.__FUNCTION__.' - Error sending Google review email - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Build email data for Google review email
     */
    private function buildGoogleReviewEmailData(ClaimRequest $claimRequest): object
    {
        return (object) [
            'uuid' => $claimRequest->uuid,
            'claimRequestId' => $claimRequest->id,
            'claimCode' => $claimRequest->code,
            'customerEmail' => $claimRequest->email,
            'customerFullName' => $claimRequest->full_name,
            'customerFirstName' => $claimRequest->first_name,
            'customerLastName' => $claimRequest->last_name,
            'customerMobile' => $claimRequest->mobile_no,
            'policyNumber' => $claimRequest->policy_number,
            'claimNumber' => $claimRequest->claim_number,
            'quoteTypeId' => $claimRequest->quote_type_id,
            'quoteTypeName' => $claimRequest->quoteType?->name ?? 'N/A',
            'insuranceProviderName' => $claimRequest->insuranceProvider?->name ?? 'N/A',
            'workflowType' => WorkflowTypeEnum::GOOGLE_REVIEW_EMAIL,
            'createdAt' => $claimRequest->created_at,
            'updatedAt' => $claimRequest->updated_at,
            'whatsappConsent' => $claimRequest->whatsapp_consent,
        ];
    }

    /**
     * Check if customer is eligible for Google review email
     */
    public function isEligibleForReviewEmail(ClaimRequest $claimRequest): bool
    {
        // Check if customer has email
        if (empty($claimRequest->email)) {
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Customer not eligible for Google review email - no email address - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
            ]);

            return false;
        }

        // Check if claim has policy number (indicates it's a valid claim)
        if (empty($claimRequest->policy_number)) {
            LoggerService::info(self::class.'::'.__FUNCTION__.' - Customer not eligible for Google review email - no policy number - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
            ]);

            return false;
        }

        // Additional eligibility checks can be added here
        // For example: checking if customer has already received a review email recently

        return true;
    }
}
