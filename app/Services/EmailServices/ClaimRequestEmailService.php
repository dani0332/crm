<?php

declare(strict_types=1);

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\QuoteTypeId;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\SendClaimGoogleReviewEmailJob;
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
     * Dispatch Google review email job for the claim request
     */
    public function dispatchClaimGoogleReviewEmail(ClaimRequest $claimRequest): void
    {
        try {
            $isLifeLob = $claimRequest->quote_type_id == QuoteTypeId::Life;

            LoggerService::info(' Dispatching Google review email job - Claim UUID: '.$claimRequest->uuid, extra: [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'customer_email' => $claimRequest->email,
                'isLifeLOB' => $isLifeLob,
            ]);

            if (! $isLifeLob) {
                // Dispatch the job to send Google review email
                SendClaimGoogleReviewEmailJob::dispatch($claimRequest->uuid);
            }

        } catch (\Exception $e) {
            LoggerService::error(' Failed to dispatch Google review email job - Claim UUID: '.$claimRequest->uuid, extra: [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send Google review email to customer
     *
     * @param  ClaimRequest  $claimRequest  The claim request that was closed
     * @return int|null HTTP status code or null if failed
     */
    public function sendClaimGoogleReviewEmail(ClaimRequest $claimRequest): ?int
    {
        try {
            LoggerService::info(' Sending Google review email - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'customer_email' => $claimRequest->email,
                'claim_status_id' => $claimRequest->claim_status_id,
            ]);

            // Build email data
            $emailData = $this->buildClaimGoogleReviewEmailData($claimRequest);

            // Get Bird webhook URL from application storage
            $googleReviewEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::CLAIM_EMAILS_WORKFLOW_URL)->first();

            if (! $googleReviewEvent) {
                LoggerService::warning(' Google review email workflow key not found - Claim UUID: '.$claimRequest->uuid, [
                    'claim_request_id' => $claimRequest->id,
                    'claim_uuid' => $claimRequest->uuid,
                ]);

                return null;
            }

            // Trigger Bird webhook
            $response = app(BirdService::class)->triggerWebHookRequest($googleReviewEvent->value, $emailData);

            LoggerService::info(' Google review email workflow triggered - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'response_status' => $response->status_code,
                'time' => now(),
            ]);

            return $response->status_code;

        } catch (Exception $e) {
            LoggerService::error(' Error sending Google review email - Claim UUID: '.$claimRequest->uuid, [
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
    private function buildClaimGoogleReviewEmailData(ClaimRequest $claimRequest): object
    {
        $phoneNumber = ! empty($claimRequest->manager->mobile_no) ? formatMobileNo($claimRequest->manager->mobile_no) : '';

        $isHealthClaim = $claimRequest->quote_type_id == QuoteTypeId::Health;
        $isGroupHealthClaim = $claimRequest->personalQuote?->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;
        $workflowType = $isHealthClaim || $isGroupHealthClaim ? WorkflowTypeEnum::CLAIM_HEALTH_GOOGLE_REVIEW_EMAIL : WorkflowTypeEnum::CLAIM_GOOGLE_REVIEW_EMAIL;

        return (object) [
            'claimUID' => $claimRequest->code ?? '',
            'claimRefId' => $claimRequest->code ?? '',
            'quoteType' => $claimRequest->quoteType?->text ?? '',
            'customerName' => $claimRequest->full_name ?? '',
            'customerEmail' => $claimRequest->email ?? '',
            'customerMobile' => formatMobileNo($claimRequest->mobile_no ?? ''),
            'managerName' => $claimRequest->manager?->name ?? '',
            'managerEmail' => $claimRequest->manager?->email ?? '',
            'managerLandLine' => $claimRequest->manager?->landline_no ?? '',
            'managerMobileNoWithoutSpaces' => $phoneNumber,
            'managerMobilePhone' => $phoneNumber,
            'managerProfilePhotoPath' => $claimRequest->manager?->profile_photo_path ?? '',
            'workflowType' => $workflowType,
            'isWAConsent' => $claimRequest->whatsapp_consent ? true : false,
        ];
    }

    /**
     * Send Google review email to customer
     *
     * @param  ClaimRequest  $claimRequest  The claim request that was closed
     * @return int|null HTTP status code or null if failed
     */
    public function sendClaimSubStatusCustomerUpdateEmail(ClaimRequest $claimRequest, $message): ?int
    {
        try {
            LoggerService::info(' Sending Claim sub status update customer email - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'customer_email' => $claimRequest->email,
                'claim_status_id' => $claimRequest->claim_status_id,
            ]);

            // Build email data
            $emailData = $this->buildClaimSubStatusCustomerUpdateEmailData($claimRequest, $message);

            // Get Bird webhook URL from application storage
            $googleReviewEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::CLAIM_EMAILS_WORKFLOW_URL)->first();

            if (! $googleReviewEvent) {
                LoggerService::warning(' Claim sub status update customer email workflow key not found - Claim UUID: '.$claimRequest->uuid, [
                    'claim_request_id' => $claimRequest->id,
                    'claim_uuid' => $claimRequest->uuid,
                ]);

                return null;
            }

            // Trigger Bird webhook
            $response = app(BirdService::class)->triggerWebHookRequest($googleReviewEvent->value, $emailData);

            LoggerService::info(' Claim sub status update customer email workflow triggered - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'response_status' => $response->status_code,
                'time' => now(),
            ]);

            return $response->status_code;

        } catch (Exception $e) {
            LoggerService::error(' Error sending Claim sub status update customer email - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function buildClaimSubStatusCustomerUpdateEmailData(ClaimRequest $claimRequest, $message): object
    {
        $phoneNumber = ! empty($claimRequest->manager->mobile_no) ? formatMobileNo($claimRequest->manager->mobile_no) : '';
        $workflowType = WorkflowTypeEnum::CLAIM_SUB_STATUS_CUSTOMER_NOTIFICATION;

        $subject = $claimRequest->code.' - '.$claimRequest->full_name.' - '.$claimRequest->quoteType?->text.' - Claim Request';
        if ($claimRequest->quote_type_id == QuoteTypeId::Health) {
            $subject = $claimRequest->code.' - '.$claimRequest->full_name.' - '.$claimRequest->quoteType?->text.' - Claim Reimbursement';
        }

        return (object) [
            'claimUID' => $claimRequest->code ?? '',
            'claimRefId' => $claimRequest->code ?? '',
            'quoteType' => $claimRequest->quoteType?->text ?? '',
            'customerName' => $claimRequest->full_name ?? '',
            'customerEmail' => $claimRequest->email ?? '',
            'customerMobile' => formatMobileNo($claimRequest->mobile_no ?? ''),
            'managerName' => $claimRequest->manager?->name ?? '',
            'managerEmail' => $claimRequest->manager?->email ?? '',
            'managerLandLine' => $claimRequest->manager?->landline_no ?? '',
            'managerMobileNoWithoutSpaces' => $phoneNumber,
            'managerMobilePhone' => $phoneNumber,
            'managerProfilePhotoPath' => $claimRequest->manager?->profile_photo_path ?? '',
            'workflowType' => $workflowType,
            'message' => $message,
            'isWAConsent' => $claimRequest->whatsapp_consent ? true : false,
            'subject' => $subject,
        ];
    }

    /**
     * Check if customer is eligible for Google review email
     */
    public function isEligibleForReviewEmail(ClaimRequest $claimRequest): bool
    {
        // Check if customer has email
        if (empty($claimRequest->email)) {
            LoggerService::info(' Customer not eligible for Google review email - no email address - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
            ]);

            return false;
        }

        return true;
    }
}
