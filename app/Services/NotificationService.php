<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteTypeCode;
use App\Events\AuthorisedPaymentCountUpdated;
use App\Events\PaymentNotifications;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Repositories\PaymentRepository;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
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
        LoggerService::info('Payment Status Update - Starting', extra: [
            'quote_type' => $quoteType,
            'quote_id' => $quoteId,
        ]);

        if (is_numeric($quoteType)) {
            LoggerService::info('Payment Status Update - Quote Type Not Valid', extra: [
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

        LoggerService::info('Payment Status Update - Quote found and validated', extra: [
            'quote_type' => $quoteType,
            'quote_id' => $quoteId,
            'quote_uuid' => $model->uuid,
            'advisor_id' => $model->advisor_id,
        ]);

        // Build URL based on quote type
        $url = $this->buildNotificationUrl($quoteType, $model);

        // Prepare quote type code for event (first 3 characters, uppercase)
        $quoteTypeCode = $this->getQuoteTypeCode($quoteType);

        LoggerService::info('Payment Status Update - Triggering PaymentNotifications event', extra: [
            'quote_uuid' => $model->uuid,
            'url' => $url,
            'quote_type_code' => $quoteTypeCode,
        ]);

        // Temporarily commented out due to Pusher quota exceeded
        // event(new PaymentNotifications($model, $url, $quoteTypeCode));

        // Broadcast authorised payment count update if this is a PersonalQuote with authorised payment
        // Temporarily commented out - event broadcasting disabled
        // $this->broadcastAuthorisedPaymentCountIfNeeded($model);

        LoggerService::info('Payment Status Update - Completed successfully', extra: [
            'quote_uuid' => $model->uuid,
        ]);

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

    /**
     * Broadcast authorised payment count update if the quote has an authorised payment.
     */
    private function broadcastAuthorisedPaymentCountIfNeeded($model): void
    {
        $advisorId = $model->advisor_id ?? null;
        if (! $advisorId) {
            LoggerService::info('Authorised Payment Count - Skipping (no advisor_id)', extra: [
                'quote_uuid' => $model->uuid,
            ]);

            return;
        }

        // Check if the quote has any payment that matches the criteria used in getAuthorisePaymentCount:
        // 1. AUTHORISED payments within the last 30 days (based on authorized_at)
        // 2. InsurerPayment (IP) with NEW status within the last 30 days (based on collection_date)
        // This ensures the broadcast trigger logic matches PaymentRepository::getAuthorisePaymentCount
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $hasAuthorisedPayment = $model->payments()
            ->where(function ($query) use ($thirtyDaysAgo) {
                $query->where(function ($q) use ($thirtyDaysAgo) {
                    $q->where('payment_status_id', PaymentStatusEnum::AUTHORISED)
                        ->where('authorized_at', '>=', $thirtyDaysAgo);
                });
            })
            ->exists();

        if (! $hasAuthorisedPayment) {
            LoggerService::info('Authorised Payment Count - Skipping (no authorised payment)', extra: [
                'quote_uuid' => $model->uuid,
                'advisor_id' => $advisorId,
            ]);

            return;
        }

        $advisor = User::find($advisorId);
        if (! $advisor) {
            LoggerService::info('Authorised Payment Count - Skipping (advisor not found)', extra: [
                'quote_uuid' => $model->uuid,
                'advisor_id' => $advisorId,
            ]);

            return;
        }

        LoggerService::info('Authorised Payment Count - Processing', extra: [
            'quote_uuid' => $model->uuid,
            'advisor_id' => $advisorId,
        ]);

        // Get all users who should see updated counts: advisor + managers of advisor's teams
        $affectedUserIds = [$advisorId];

        // Get advisor's team IDs
        $advisorTeamIds = $advisor->getUserTeamIds();

        if (! empty($advisorTeamIds)) {
            // Get all managers who manage teams that the advisor belongs to
            $managerRoles = getManagerRoles();

            $managerUserIds = User::whereHas('roles', function ($query) use ($managerRoles) {
                $query->whereIn('name', $managerRoles);
            })
                ->whereHas('teams', function ($query) use ($advisorTeamIds) {
                    $query->whereIn('teams.id', $advisorTeamIds);
                })
                ->pluck('users.id')
                ->toArray();

            $affectedUserIds = array_unique(array_merge($affectedUserIds, $managerUserIds));

            LoggerService::info('Authorised Payment Count - Found managers', extra: [
                'quote_uuid' => $model->uuid,
                'advisor_id' => $advisorId,
                'team_ids' => $advisorTeamIds,
                'manager_count' => count($managerUserIds),
            ]);
        }

        // Calculate and broadcast count for each affected user
        $paymentRepository = app(PaymentRepository::class);

        LoggerService::info('Authorised Payment Count - Broadcasting to users', extra: [
            'quote_uuid' => $model->uuid,
            'total_users' => count($affectedUserIds),
        ]);

        foreach ($affectedUserIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $count = $paymentRepository->getAuthorisePaymentCount($user);
            // Temporarily commented out - event broadcasting disabled
            // event(new AuthorisedPaymentCountUpdated($userId, $count));
        }

        LoggerService::info('Authorised Payment Count - Completed', extra: [
            'quote_uuid' => $model->uuid,
            'affected_users' => $affectedUserIds,
            'total_users' => count($affectedUserIds),
        ]);
    }
}
