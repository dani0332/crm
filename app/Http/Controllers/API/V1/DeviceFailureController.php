<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Enums\DeviceFailureTypeEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeviceFailureEmailRequest;
use App\Models\PersonalQuote;
use App\Services\DeviceFailureEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Http\JsonResponse;

class DeviceFailureController extends Controller
{
    private string $logPrefix = 'DeviceFailureController:';

    public function __construct(
        private DeviceFailureEmailService $failureEmailService
    ) {}

    /**
     * Generic failure notification endpoint
     *
     * POST /api/v1/device/failure-email
     *
     * @param DeviceFailureEmailRequest $request
     * @return JsonResponse
     */
    public function failureEmail(DeviceFailureEmailRequest $request): JsonResponse
    {
        LoggerService::info("{$this->logPrefix} Failure notification endpoint called", extra: [
            'request_data' => $request->all(),
        ]);

        $validated = $request->validated();

        $quoteUuid = $validated['quote_uuid'];
        $failureType = DeviceFailureTypeEnum::from($validated['failure_type']);
        $providerCode = $validated['provider_code'] ?? InsuranceProviderEnum::NGI->value;

        // Find and validate quote - single DB query
        $quote = PersonalQuote::where('uuid', $quoteUuid)->first();

        // Validate quote, LOB, and provider in one check
        $validationError = $this->getQuoteValidationError($quote, $quoteUuid, $providerCode);
        if ($validationError !== null) {
            return response()->json([
                'success' => false,
                'message' => $validationError['message'],
                'error_code' => $validationError['error_code'],
            ], $validationError['status_code']);
        }

        // Send the failure email and return appropriate response
        return $this->processFailureEmail($quote, $failureType, $providerCode, $quoteUuid);
    }

    /**
     * Process failure email and return response
     *
     * @param PersonalQuote $quote
     * @param DeviceFailureTypeEnum $failureType
     * @param string $providerCode
     * @param string $quoteUuid
     * @return JsonResponse
     */
    private function processFailureEmail(
        PersonalQuote $quote,
        DeviceFailureTypeEnum $failureType,
        string $providerCode,
        string $quoteUuid
    ): JsonResponse {
        $emailSent = $this->failureEmailService->sendFailureEmail(
            $quote->id,
            $failureType,
            $providerCode
        );

        if ($emailSent) {
            LoggerService::info("{$this->logPrefix} Failure notification email dispatched", extra: [
                'quote_uuid' => $quoteUuid,
                'failure_type' => $failureType->value,
                'ref_id' => $quote->code,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Failure notification email has been queued',
                'ref_id' => $quote->code,
                'failure_type' => $failureType->value,
                'trigger_point' => $failureType->getTriggerPointText(),
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to queue failure notification email',
            'error_code' => 'EMAIL_DISPATCH_FAILED',
        ], 500);
    }

    /**
     * Get validation error for quote, or null if valid
     *
     * @param PersonalQuote|null $quote
     * @param string $quoteUuid
     * @param string $providerCode
     * @return array{message: string, error_code: string, status_code: int}|null
     */
    private function getQuoteValidationError(?PersonalQuote $quote, string $quoteUuid, string $providerCode): ?array
    {
        if (! $quote) {
            LoggerService::error("{$this->logPrefix} Quote not found", extra: [
                'quote_uuid' => $quoteUuid,
            ]);

            return [
                'message' => 'Quote not found',
                'error_code' => 'QUOTE_NOT_FOUND',
                'status_code' => 404,
            ];
        }

        // Check LOB and provider validation - return first error found or null if valid
        $lobProviderError = $this->getLobOrProviderError($quote, $providerCode);
        if ($lobProviderError !== null) {
            return $lobProviderError;
        }

        return null;
    }

    /**
     * Get LOB or provider validation error, or null if valid
     *
     * @param PersonalQuote $quote
     * @param string $providerCode
     * @return array{message: string, error_code: string, status_code: int}|null
     */
    private function getLobOrProviderError(PersonalQuote $quote, string $providerCode): ?array
    {
        $isValidLob = $quote->quote_type_id === QuoteTypes::getId(QuoteTypes::DEVICE);
        if (! $isValidLob) {
            return [
                'message' => 'Quote is not a Device insurance quote',
                'error_code' => 'INVALID_LOB',
                'status_code' => 400,
            ];
        }

        $isValidProvider = $providerCode === InsuranceProviderEnum::NGI->value;
        if (! $isValidProvider) {
            return [
                'message' => 'Provider must be NGI for Device insurance',
                'error_code' => 'INVALID_PROVIDER',
                'status_code' => 400,
            ];
        }

        return null;
    }
}
