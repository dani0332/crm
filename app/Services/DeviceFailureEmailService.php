<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DeviceFailureTypeEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\SendDeviceFailureEmailJob;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;

class DeviceFailureEmailService
{
    private string $logPrefix = 'DeviceFailureEmailService:';

    /**
     * Dispatch failure email job for Device/NGI quotes
     * This is the main entry point - validates LOB and Provider before dispatching
     *
     * @param int $quoteId
     * @param DeviceFailureTypeEnum $failureType
     * @param string|null $providerCode
     * @return bool
     */
    public function sendFailureEmail(
        int $quoteId,
        DeviceFailureTypeEnum $failureType,
        ?string $providerCode = null
    ): bool {
        LoggerService::info("{$this->logPrefix} Initiating failure email", extra: [
            'quoteId' => $quoteId,
            'failureType' => $failureType->value,
            'providerCode' => $providerCode,
        ]);

        $quote = PersonalQuote::find($quoteId);

        // Validate quote exists and is a valid Device/NGI quote - combined validation
        if (! $this->isValidQuoteForFailureEmail($quote, $quoteId, $providerCode)) {
            return false;
        }

        // Dispatch the job
        SendDeviceFailureEmailJob::dispatch(
            $quoteId,
            $failureType,
            InsuranceProviderEnum::NGI->value
        )->delay(now()->addSeconds(5));

        LoggerService::info("{$this->logPrefix} Failure email job dispatched", extra: [
            'quoteId' => $quoteId,
            'failureType' => $failureType->value,
            'refId' => $quote->code,
        ]);

        return true;
    }

    /**
     * Validate quote for failure email dispatch
     * Checks: quote exists, is Device LOB, and is NGI provider
     *
     * @param PersonalQuote|null $quote
     * @param int $quoteId
     * @param string|null $providerCode
     * @return bool
     */
    private function isValidQuoteForFailureEmail(?PersonalQuote $quote, int $quoteId, ?string $providerCode): bool
    {
        if (! $quote) {
            LoggerService::error("{$this->logPrefix} Quote not found", extra: [
                'quoteId' => $quoteId,
            ]);
            return false;
        }

        // Combined LOB and Provider validation using existing method
        $effectiveProviderCode = $providerCode ?? $quote->insuranceProvider?->code;
        if (! $this->isValidDeviceNgiQuote($quote, $effectiveProviderCode)) {
            $this->logInvalidQuoteWarning($quote, $quoteId, $effectiveProviderCode);
            return false;
        }

        return true;
    }

    /**
     * Log warning for invalid Device/NGI quote
     *
     * @param PersonalQuote $quote
     * @param int $quoteId
     * @param string|null $providerCode
     * @return void
     */
    private function logInvalidQuoteWarning(PersonalQuote $quote, int $quoteId, ?string $providerCode): void
    {
        $isDeviceLob = $this->isDeviceLob($quote);

        if (! $isDeviceLob) {
            LoggerService::warning("{$this->logPrefix} Not a Device LOB quote, skipping", extra: [
                'quoteId' => $quoteId,
                'quoteTypeId' => $quote->quote_type_id,
            ]);
            return;
        }

        LoggerService::warning("{$this->logPrefix} Not an NGI provider quote, skipping", extra: [
            'quoteId' => $quoteId,
            'providerCode' => $providerCode,
        ]);
    }

    /**
     * Trigger failure email from insurer API status ID
     * Used by PolicyIssuanceService and NgiInsuranceService
     *
     * @param int $quoteId
     * @param int|null $insurerApiStatus
     * @param string|null $completedStep Fallback to determine failure type
     * @return bool
     */
    public function sendFailureEmailFromStatus(
        int $quoteId,
        ?int $insurerApiStatus,
        ?string $completedStep = null
    ): bool {
        $failureType = $this->determineFailureTypeFromStatus($insurerApiStatus, $completedStep);

        if (! $failureType) {
            LoggerService::warning("{$this->logPrefix} Could not determine failure type", extra: [
                'quoteId' => $quoteId,
                'insurerApiStatus' => $insurerApiStatus,
                'completedStep' => $completedStep,
            ]);
            return false;
        }

        return $this->sendFailureEmail($quoteId, $failureType);
    }

    /**
     * Determine failure type from insurer API status or completed step
     *
     * @param int|null $insurerApiStatus
     * @param string|null $completedStep
     * @return DeviceFailureTypeEnum|null
     */
    public function determineFailureTypeFromStatus(?int $insurerApiStatus, ?string $completedStep = null): ?DeviceFailureTypeEnum
    {
        // First try to determine from insurer API status
        if ($insurerApiStatus !== null) {
            $failureType = match ($insurerApiStatus) {
                PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID => DeviceFailureTypeEnum::ISSUE_POLICY,
                PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID => DeviceFailureTypeEnum::GET_AND_UPLOAD_DOCUMENTS,
                PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID => DeviceFailureTypeEnum::BOOK_POLICY,
                default => null,
            };

            if ($failureType !== null) {
                return $failureType;
            }
        }

        // Fallback: determine from completed step (next step that failed)
        if ($completedStep !== null) {
            return match ($completedStep) {
                '', NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE => DeviceFailureTypeEnum::GET_AND_UPLOAD_DOCUMENTS,
                NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM => DeviceFailureTypeEnum::BOOK_POLICY,
                default => DeviceFailureTypeEnum::ISSUE_POLICY,
            };
        }

        // Default to policy details API failure (first step)
        return DeviceFailureTypeEnum::ISSUE_POLICY;
    }

    /**
     * Check if quote is Device LOB
     *
     * @param PersonalQuote $quote
     * @return bool
     */
    public function isDeviceLob(PersonalQuote $quote): bool
    {
        return $quote->quote_type_id === QuoteTypes::getId(QuoteTypes::DEVICE);
    }

    /**
     * Check if provider is NGI
     *
     * @param string|null $providerCode
     * @return bool
     */
    public function isNgiProvider(?string $providerCode): bool
    {
        return $providerCode === InsuranceProviderEnum::NGI->value;
    }

    /**
     * Check if quote is valid Device/NGI quote
     *
     * @param PersonalQuote $quote
     * @param string|null $providerCode
     * @return bool
     */
    public function isValidDeviceNgiQuote(PersonalQuote $quote, ?string $providerCode = null): bool
    {
        $effectiveProviderCode = $providerCode ?? $quote->insuranceProvider?->code;
        return $this->isDeviceLob($quote) && $this->isNgiProvider($effectiveProviderCode);
    }
}
