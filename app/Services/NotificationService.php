<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteTypeCode;
use App\Events\PaymentNotifications;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\JsonResponse;

class NotificationService extends BaseService
{
    use GenericQueriesAllLobs;

    /**
     * Send payment status update notification to advisor.
     *
     * @param  string  $quoteType  The type of quote (e.g., 'Car', 'Business')
     * @param  string  $quoteId  The UUID of the quote
     */
    public function paymentStatusUpdate(string $quoteType, string $quoteId): JsonResponse
    {
        if (is_numeric($quoteType)) {
            LoggerService::info('Payment Status Update API - Quote Type Not Valid', extra: [
                'quote_type' => $quoteType,
                'quote_id' => $quoteId,
                'reason' => 'Quote type must be a string, not numeric',
            ]);

            return response()->json(['message' => 'Quote type not valid'], 422);
        }

        $model = $this->getQuoteObjectBy($quoteType, $quoteId, 'uuid');
        $validationError = $this->validateModel($model, $quoteType, $quoteId);
        if ($validationError !== null) {
            return $validationError;
        }

        // Build URL based on quote type
        $url = $this->buildNotificationUrl($quoteType, $model);

        // Prepare quote type code for event (first 3 characters, uppercase)
        $quoteTypeCode = $this->getQuoteTypeCode($quoteType);

        info('Payment Notification Event Trigger: '.$model->uuid);
        event(new PaymentNotifications($model, $url, $quoteTypeCode));

        return response()->json(['message' => 'Payment notification successfully sent to advisor']);
    }

    private function validateModel($model, string $quoteType, string $quoteId): ?JsonResponse
    {
        if (! $model) {
            LoggerService::info('Payment Status Update API - Quote Not Found', extra: [
                'quote_type' => $quoteType,
                'quote_id' => $quoteId,
                'reason' => 'Quote not found with provided quoteType and quoteId',
            ]);

            return response()->json(['message' => 'Quote not found'], 404);
        }

        if ($model->advisor_id === null) {
            LoggerService::info('Payment Status Update API - No Advisor Assigned to this Lead', extra: [
                'quote_type' => $quoteType,
                'quote_id' => $quoteId,
                'reason' => 'No advisor assigned to this lead',
            ]);

            return response()->json(['message' => 'No advisor assigned to this lead'], 422);
        }

        return null;
    }

    private function buildNotificationUrl(string $quoteType, $model): string
    {
        // Check for personal quotes first to avoid overwriting URL
        if (checkPersonalQuotes(ucwords($quoteType))) {
            return '/personal-quotes/'.strtolower($quoteType).'/'.$model->uuid;
        }

        $baseUrl = url('/');
        $url = $baseUrl.'/quotes/'.strtolower($quoteType).'/'.$model->uuid;

        if ($quoteType === quoteTypeCode::Business) {
            $businessTypeId = $model->business_type_of_insurance_id ?? null;
            $url = $businessTypeId === quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)
                ? $baseUrl."/medical/amt/$model->uuid"
                : $baseUrl."/quotes/business/$model->uuid";
        }

        return $url;
    }

    private function getQuoteTypeCode(string $quoteType): string
    {
        $trimmed = trim($quoteType);
        $length = strlen($trimmed);

        // Ensure we don't exceed the string length
        $codeLength = min(3, $length);

        return strtoupper(substr($trimmed, 0, $codeLength));
    }
}
