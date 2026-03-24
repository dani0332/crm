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
        $originalClaimRequest = $claimRequest;

        try {
            $claimRequest = $claimRequest->fresh();

            if ($claimRequest === null) {
                LoggerService::warning(' Cannot dispatch Google review email - claim request no longer exists', extra: [
                    'claim_request_id' => $originalClaimRequest->id,
                    'claim_uuid' => $originalClaimRequest->uuid,
                ]);

                return;
            }

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

        } catch (\Throwable $e) {
            LoggerService::error(' Failed to dispatch Google review email job - Claim UUID: '.$originalClaimRequest->uuid, extra: [
                'claim_request_id' => $originalClaimRequest->id,
                'claim_uuid' => $originalClaimRequest->uuid,
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
     * Whether the claim follows health / group-medical routing:
     * - Health LOB
     * - Business LOB with group-medical business type (business_type_of_insurance_id = 5)
     * - Personal quote flagged as group medical
     */
    private function claimUsesHealthOrGroupMedicalFlow(ClaimRequest $claimRequest): bool
    {
        if ($claimRequest->quote_type_id == QuoteTypeId::Health) {
            return true;
        }

        if ($claimRequest->quote_type_id == QuoteTypeId::Business
            && $claimRequest->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL) {
            return true;
        }

        return $claimRequest->personalQuote?->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;
    }

    /**
     * Build email data for Google review email
     */
    private function buildClaimGoogleReviewEmailData(ClaimRequest $claimRequest): object
    {
        $phoneNumber = ! empty($claimRequest->manager?->mobile_no) ? formatMobileNo($claimRequest->manager->mobile_no) : '';

        $isHealthOrGroupMedicalFlow = $this->claimUsesHealthOrGroupMedicalFlow($claimRequest);
        $workflowType = $isHealthOrGroupMedicalFlow
            ? WorkflowTypeEnum::CLAIM_HEALTH_GOOGLE_REVIEW_EMAIL
            : WorkflowTypeEnum::CLAIM_GOOGLE_REVIEW_EMAIL;

        $bccStorageKey = $isHealthOrGroupMedicalFlow
            ? ApplicationStorageEnums::CLAIM_HEALTH_GOOGLE_REVIEW_EMAIL_BCC
            : ApplicationStorageEnums::CLAIM_GOOGLE_REVIEW_EMAIL_BCC;

        $claimGoogleReviewBccRaw = getAppStorageValueByKey($bccStorageKey, '');
        $claimGoogleReviewBcc = array_values(array_filter(
            array_map('trim', explode(',', (string) ($claimGoogleReviewBccRaw ?: ''))),
        ));

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
            'emailBcc' => $claimGoogleReviewBcc,
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
        $phoneNumber = ! empty($claimRequest->manager?->mobile_no) ? formatMobileNo($claimRequest->manager->mobile_no) : '';
        $workflowType = WorkflowTypeEnum::CLAIM_SUB_STATUS_CUSTOMER_NOTIFICATION;

        $subject = $claimRequest->code.' - '.$claimRequest->full_name.' - '.$claimRequest->quoteType?->text.' - Claim Request';
        if ($this->claimUsesHealthOrGroupMedicalFlow($claimRequest)) {
            $subject = $claimRequest->code.' - '.$claimRequest->full_name.' - '.$claimRequest->quoteType?->text.' - Claim Reimbursement';
        }

        $subStatusBccStorageKey = $this->resolveClaimSubStatusCustomerEmailBccStorageKey($claimRequest);
        $subStatusBccRaw = getAppStorageValueByKey($subStatusBccStorageKey, '');
        $subStatusBcc = array_values(array_filter(
            array_map('trim', explode(',', (string) ($subStatusBccRaw ?: ''))),
        ));

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
            'emailBcc' => $subStatusBcc,
        ];
    }

    /**
     * Which application_storage BCC key to use for sub-status customer emails (LOB-specific).
     */
    private function resolveClaimSubStatusCustomerEmailBccStorageKey(ClaimRequest $claimRequest): string
    {
        if ($claimRequest->quote_type_id == QuoteTypeId::Life) {
            return ApplicationStorageEnums::CLAIM_SUB_STATUS_CUSTOMER_EMAIL_BCC_LIFE;
        }

        if ($this->claimUsesHealthOrGroupMedicalFlow($claimRequest)) {
            return ApplicationStorageEnums::CLAIM_SUB_STATUS_CUSTOMER_EMAIL_BCC_HEALTH;
        }

        return ApplicationStorageEnums::CLAIM_SUB_STATUS_CUSTOMER_EMAIL_BCC_MOTOR_AND_GENERAL;
    }

    /**
     * Check if customer is eligible for Google review email
     */
    public function isEligibleForReviewEmail(ClaimRequest $claimRequest): bool
    {
        return $this->hasCustomerEmailForClaim($claimRequest, 'Google review email');
    }

    /**
     * Check if customer is eligible for Claim sub status update email
     */
    public function isEligibleForSubStatusUpdateEmail(ClaimRequest $claimRequest): bool
    {
        return $this->hasCustomerEmailForClaim($claimRequest, 'Claim sub status update email');
    }

    private function hasCustomerEmailForClaim(ClaimRequest $claimRequest, string $emailContext): bool
    {
        if (empty($claimRequest->email)) {
            LoggerService::info(' Customer not eligible for '.$emailContext.' - no email address - Claim UUID: '.$claimRequest->uuid, [
                'claim_request_id' => $claimRequest->id,
                'claim_uuid' => $claimRequest->uuid,
            ]);

            return false;
        }

        return true;
    }
}
