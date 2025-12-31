<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DeviceFailureTypeEnum;
use App\Enums\EnvEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Exceptions\BirdWebhookException;
use App\Jobs\SendDeviceFailureEmailJob;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;

class DeviceFailureEmailService
{
    private string $logPrefix = 'DeviceFailureEmailService:';

    public function __construct(
        private readonly BirdService $birdService
    ) {}

    // /**
    //  * Dispatch failure email job for Device/NGI quotes
    //  * This is the main entry point - validates LOB and Provider before dispatching
    //  */
    // public function sendFailureEmail(
    //     int $quoteId,
    //     DeviceFailureTypeEnum $failureType,
    //     ?string $providerCode = null
    // ): bool {
    //     LoggerService::info("{$this->logPrefix} Initiating failure email", extra: [
    //         'quoteId' => $quoteId,
    //         'failureType' => $failureType->value,
    //         'providerCode' => $providerCode,
    //     ]);

    //     $quote = PersonalQuote::find($quoteId);

    //     // Validate quote exists and is a valid Device/NGI quote - combined validation
    //     if (! $this->isValidQuoteForFailureEmail($quote, $quoteId, $providerCode)) {
    //         return false;
    //     }

    //     // Dispatch the job
    //     SendDeviceFailureEmailJob::dispatch(
    //         $quoteId,
    //         $failureType,
    //         InsuranceProviderEnum::NGI->value
    //     )->delay(now()->addSeconds(5));

    //     LoggerService::info("{$this->logPrefix} Failure email job dispatched", extra: [
    //         'quoteId' => $quoteId,
    //         'failureType' => $failureType->value,
    //         'refId' => $quote->code,
    //     ]);

    //     return true;
    // }

    // /**
    //  * Execute sending failure email via Bird webhook.
    //  * This is called by the SendDeviceFailureEmailJob.
    //  *
    //  * @param  int  $attempt  Current attempt number
    //  * @return array{status: bool, error?: string}
    //  *
    //  * @throws BirdWebhookException When Bird webhook fails
    //  */
    // public function executeFailureEmail(
    //     int $quoteId,
    //     DeviceFailureTypeEnum $failureType,
    //     ?string $providerCode = null,
    //     int $attempt = 1
    // ): array {
    //     LoggerService::info("{$this->logPrefix} Starting failure email execution", extra: [
    //         'quoteId' => $quoteId,
    //         'failureType' => $failureType->value,
    //         'attempt' => $attempt,
    //     ]);

    //     $quote = PersonalQuote::with(['advisor', 'customer'])->find($quoteId);

    //     if (! $quote) {
    //         LoggerService::error("{$this->logPrefix} Quote not found", extra: [
    //             'quoteId' => $quoteId,
    //         ]);

    //         return ['status' => false, 'error' => 'Quote not found'];
    //     }

    //     // Validate LOB is Device and Provider is NGI
    //     if (! $this->isValidDeviceNgiQuote($quote, $providerCode)) {
    //         LoggerService::warning("{$this->logPrefix} Skipping - not a valid Device/NGI quote", extra: [
    //             'quoteId' => $quoteId,
    //             'quoteTypeId' => $quote->quote_type_id,
    //         ]);

    //         return ['status' => false, 'error' => 'Not a valid Device/NGI quote'];
    //     }

    //     // Send email via Bird
    //     $response = $this->sendViaBird($quote, $failureType);

    //     if ($response === 200) {
    //         $this->logFailureAttempt($quote, $failureType, $attempt);
    //         LoggerService::info("{$this->logPrefix} Failure email sent successfully via Bird", extra: [
    //             'quoteId' => $quoteId,
    //             'failureType' => $failureType->value,
    //             'refId' => $quote->code,
    //         ]);

    //         return ['status' => true];
    //     }

    //     throw new BirdWebhookException(
    //         "Bird webhook returned status: {$response}",
    //         BirdWebhookException::WEBHOOK_FAILED,
    //         $response
    //     );
    // }

    // /**
    //  * Send failure email via Bird webhook
    //  */
    // private function sendViaBird(PersonalQuote $quote, DeviceFailureTypeEnum $failureType): ?int
    // {
    //     $appEnv = config('constants.APP_ENV');

    //     // Build CC emails
    //     $cc = $this->buildCcEmails($quote, $failureType, $appEnv);

    //     // Determine recipient based on failure type
    //     $recipient = $this->getRecipient($quote, $failureType);

    //     // Build email data for Bird workflow
    //     $emailData = (object) [
    //         'quoteUID' => $quote->uuid,
    //         'refId' => $quote->code,
    //         'customerEmail' => $recipient['email'],
    //         'customerName' => $recipient['name'],
    //         'recipientEmail' => $recipient['email'],
    //         'recipientName' => $recipient['name'],
    //         'triggerPoint' => $failureType->getTriggerPointText(),
    //         'failureType' => $failureType->getDisplayName(),
    //         'imcrmReferenceNumber' => $quote->code,
    //         'imcrmLink' => $this->generateImcrmLink($quote),
    //         'escalationLink' => $this->getEscalationLink(),
    //         'cc' => $cc,
    //         'workflowType' => WorkflowTypeEnum::DEVICE_AUTOMATION_FAILED,
    //     ];

    //     // Get Bird workflow URL from ApplicationStorage
    //     $birdUrl = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_BIRD_URL);

    //     if (empty($birdUrl)) {
    //         LoggerService::warning("{$this->logPrefix} Bird URL not found in ApplicationStorage", extra: [
    //             'key' => ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_BIRD_URL,
    //         ]);

    //         return null;
    //     }

    //     LoggerService::info("{$this->logPrefix} Triggering Bird webhook", extra: [
    //         'quoteId' => $quote->id,
    //         'url' => $birdUrl,
    //         'emailData' => $emailData,
    //     ]);

    //     $response = $this->birdService->triggerWebHookRequest($birdUrl, $emailData);

    //     return $response?->status_code;
    // }

    // /**
    //  * Get recipient based on failure type (FRD requirement)
    //  *
    //  * @return array{email: string, name: string}
    //  */
    // private function getRecipient(PersonalQuote $quote, DeviceFailureTypeEnum $failureType): array
    // {
    //     // Booking Details API failure goes to Production Approval Team
    //     if ($failureType === DeviceFailureTypeEnum::BOOK_POLICY) {
    //         $toEmail = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_TO)
    //             ?: 'production.approval.team@insurancemarket.ae';

    //         return ['email' => $toEmail, 'name' => 'Production Approval Team'];
    //     }

    //     // Other failures go to assigned advisor
    //     if ($quote->advisor) {
    //         return ['email' => $quote->advisor->email, 'name' => $quote->advisor->name];
    //     }

    //     // Fallback
    //     $toEmail = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_TO)
    //         ?: 'production.approval.team@insurancemarket.ae';

    //     return ['email' => $toEmail, 'name' => 'Device Support Team'];
    // }

    // /**
    //  * Build CC emails
    //  *
    //  * @return array{approvalemail: string|null, prodemail: string|null, advisoremail: string|null}
    //  */
    // private function buildCcEmails(PersonalQuote $quote, DeviceFailureTypeEnum $failureType, string $appEnv): array
    // {
    //     $cc = [
    //         'approvalemail' => null,
    //         'prodemail' => null,
    //         'advisoremail' => null,
    //     ];

    //     if (in_array($appEnv, [EnvEnum::PRODUCTION, EnvEnum::STAGING])) {
    //         $cc['approvalemail'] = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_CC);
    //         $cc['prodemail'] = getAppStorageValueByKey(ApplicationStorageEnums::PRODUCTION_APPROVAL_EMAIL);

    //         // Include advisor in CC for booking failures
    //         if ($failureType === DeviceFailureTypeEnum::BOOK_POLICY && $quote->advisor) {
    //             $cc['advisoremail'] = $quote->advisor->email;
    //         }
    //     }

    //     return $cc;
    // }

    // /**
    //  * Generate IMCRM link for the quote
    //  */
    // private function generateImcrmLink(PersonalQuote $quote): string
    // {
    //     $baseUrl = config('app.url', env('APP_URL'));

    //     return "{$baseUrl}/personal-quotes/device/{$quote->uuid}";
    // }

    // /**
    //  * Get escalation link from ApplicationStorage
    //  */
    // private function getEscalationLink(): string
    // {
    //     $link = getAppStorageValueByKey(ApplicationStorageEnums::DEVICE_FAILURE_EMAIL_ESCALATION_LINK);

    //     return $link ?: 'https://forms.clickup.com/2197982/f/232ey-57398/E5NVOINDYMZRFPTA3T';
    // }

    // /**
    //  * Log the failure attempt to API logs
    //  */
    // private function logFailureAttempt(PersonalQuote $quote, DeviceFailureTypeEnum $failureType, int $attempt): void
    // {
    //     // Log to API logs table for auditing
    //     $quote->apiLogs()->create([
    //         'request_type' => 'FAILURE_EMAIL_SENT_VIA_BIRD',
    //         'request_data' => json_encode([
    //             'failure_type' => $failureType->value,
    //             'trigger_point' => $failureType->getTriggerPointText(),
    //             'attempt' => $attempt,
    //         ]),
    //         'response_data' => json_encode([
    //             'status' => 'sent',
    //             'sent_at' => now()->toIso8601String(),
    //         ]),
    //         'status' => 'success',
    //     ]);
    // }

    // /**
    //  * Handle job failure - log the error
    //  */
    // public function handleJobFailure(int $quoteId, DeviceFailureTypeEnum $failureType, string $errorMessage): void
    // {
    //     LoggerService::error("{$this->logPrefix} Job failed after all retries", extra: [
    //         'quoteId' => $quoteId,
    //         'failureType' => $failureType->value,
    //         'error' => $errorMessage,
    //     ]);
    // }

    // /**
    //  * Validate quote for failure email dispatch
    //  * Checks: quote exists, is Device LOB, and is NGI provider
    //  */
    // private function isValidQuoteForFailureEmail(?PersonalQuote $quote, int $quoteId, ?string $providerCode): bool
    // {
    //     if (! $quote) {
    //         LoggerService::error("{$this->logPrefix} Quote not found", extra: [
    //             'quoteId' => $quoteId,
    //         ]);

    //         return false;
    //     }

    //     // Combined LOB and Provider validation using existing method
    //     $effectiveProviderCode = $providerCode ?? $quote->insuranceProvider?->code;
    //     if (! $this->isValidDeviceNgiQuote($quote, $effectiveProviderCode)) {
    //         $this->logInvalidQuoteWarning($quote, $quoteId, $effectiveProviderCode);

    //         return false;
    //     }

    //     return true;
    // }

    // /**
    //  * Log warning for invalid Device/NGI quote
    //  */
    // private function logInvalidQuoteWarning(PersonalQuote $quote, int $quoteId, ?string $providerCode): void
    // {
    //     $isDeviceLob = $this->isDeviceLob($quote);

    //     if (! $isDeviceLob) {
    //         LoggerService::warning("{$this->logPrefix} Not a Device LOB quote, skipping", extra: [
    //             'quoteId' => $quoteId,
    //             'quoteTypeId' => $quote->quote_type_id,
    //         ]);

    //         return;
    //     }

    //     LoggerService::warning("{$this->logPrefix} Not an NGI provider quote, skipping", extra: [
    //         'quoteId' => $quoteId,
    //         'providerCode' => $providerCode,
    //     ]);
    // }

    // /**
    //  * Trigger failure email from insurer API status ID
    //  * Used by PolicyIssuanceService and NgiInsuranceService
    //  *
    //  * @param  string|null  $completedStep  Fallback to determine failure type
    //  */
    // public function sendFailureEmailFromStatus(
    //     int $quoteId,
    //     ?int $insurerApiStatusId,
    //     ?string $completedStep = null
    // ): bool {
    //     $failureType = $this->determineFailureTypeFromStatus($insurerApiStatusId, $completedStep);

    //     if (! $failureType) {
    //         LoggerService::warning("{$this->logPrefix} Could not determine failure type", extra: [
    //             'quoteId' => $quoteId,
    //             'insurerApiStatusId' => $insurerApiStatusId,
    //             'completedStep' => $completedStep,
    //         ]);

    //         return false;
    //     }

    //     return $this->sendFailureEmail($quoteId, $failureType);
    // }

    // /**
    //  * Determine failure type from insurer API status or completed step
    //  */
    // public function determineFailureTypeFromStatus(?int $insurerApiStatusId, ?string $completedStep = null): ?DeviceFailureTypeEnum
    // {
    //     // First try to determine from insurer API status
    //     if ($insurerApiStatusId !== null) {
    //         $failureType = match ($insurerApiStatusId) {
    //             PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID => DeviceFailureTypeEnum::ISSUE_POLICY,
    //             PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID => DeviceFailureTypeEnum::GET_AND_UPLOAD_DOCUMENTS,
    //             PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID => DeviceFailureTypeEnum::BOOK_POLICY,
    //             default => null,
    //         };

    //         if ($failureType !== null) {
    //             return $failureType;
    //         }
    //     }

    //     // Fallback: determine from completed step (next step that failed)
    //     if ($completedStep !== null) {
    //         return match ($completedStep) {
    //             NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE => DeviceFailureTypeEnum::GET_AND_UPLOAD_DOCUMENTS,
    //             NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM => DeviceFailureTypeEnum::BOOK_POLICY,
    //             default => DeviceFailureTypeEnum::ISSUE_POLICY,
    //         };
    //     }

    //     // Default to policy details API failure (first step)
    //     return DeviceFailureTypeEnum::ISSUE_POLICY;
    // }

    // /**
    //  * Check if quote is Device LOB
    //  */
    // public function isDeviceLob(PersonalQuote $quote): bool
    // {
    //     return $quote->quote_type_id === QuoteTypes::getId(QuoteTypes::DEVICE);
    // }

    // /**
    //  * Check if provider is NGI
    //  */
    // public function isNgiProvider(?string $providerCode): bool
    // {
    //     return $providerCode === InsuranceProviderEnum::NGI->value;
    // }

    // /**
    //  * Check if quote is valid Device/NGI quote
    //  */
    // public function isValidDeviceNgiQuote(PersonalQuote $quote, ?string $providerCode = null): bool
    // {
    //     $effectiveProviderCode = $providerCode ?? $quote->insuranceProvider?->code;

    //     return $this->isDeviceLob($quote) && $this->isNgiProvider($effectiveProviderCode);
    // }
}
