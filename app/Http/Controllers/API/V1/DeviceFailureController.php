<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Enums\DeviceFailureTypeEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Models\PersonalQuote;
use App\Services\DeviceFailureEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
     * @param Request $request
     * @return JsonResponse
     */
    public function failureEmail(Request $request): JsonResponse
    {
        LoggerService::info("{$this->logPrefix} Failure notification endpoint called", extra: [
            'request_data' => $request->all(),
        ]);

        $validator = Validator::make($request->all(), [
            'quote_uuid' => 'required|string',
            'failure_type' => 'required|string|in:'.DeviceFailureTypeEnum::BOOK_POLICY->value.','.DeviceFailureTypeEnum::AUTO_CAPTURE_PAYMENT->value.','.DeviceFailureTypeEnum::ISSUE_POLICY->value.','.DeviceFailureTypeEnum::GET_AND_UPLOAD_DOCUMENTS->value,
            'provider_code' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $quoteUuid = $validated['quote_uuid'];
        $failureType = DeviceFailureTypeEnum::from($validated['failure_type']);
        $providerCode = $validated['provider_code'] ?? InsuranceProviderEnum::NGI->value;

        // Find the quote
        $quote = PersonalQuote::where('uuid', $quoteUuid)->first();

        if (! $quote) {
            LoggerService::error("{$this->logPrefix} Quote not found", extra: [
                'quote_uuid' => $quoteUuid,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Quote not found',
                'error_code' => 'QUOTE_NOT_FOUND',
            ], 404);
        }

        // Validate LOB is Device and Provider is NGI
        if ($quote->quote_type_id !== QuoteTypes::getId(QuoteTypes::DEVICE)) {
            return response()->json([
                'success' => false,
                'message' => 'Quote is not a Device insurance quote',
                'error_code' => 'INVALID_LOB',
            ], 400);
        }

        if ($providerCode !== InsuranceProviderEnum::NGI->value) {
            return response()->json([
                'success' => false,
                'message' => 'Provider must be NGI for Device insurance',
                'error_code' => 'INVALID_PROVIDER',
            ], 400);
        }

        // Send the failure email
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
}
