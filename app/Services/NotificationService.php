<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Events\AuthorisedPaymentCountUpdated;
use App\Events\PaymentNotifications;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Repositories\PaymentRepository;
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

        // Broadcast authorised payment count update if this is a PersonalQuote with authorised payment
        $this->broadcastAuthorisedPaymentCountIfNeeded($model);

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
        // Only process PersonalQuote (getAuthorisePaymentCount only works with personal_quotes)
        if (! ($model instanceof PersonalQuote)) {
            return;
        }

        $advisorId = $model->advisor_id ?? null;
        if (! $advisorId) {
            return;
        }

        // Check if the quote has any payment with AUTHORISED status
        $hasAuthorisedPayment = $model->payments()
            ->where('payment_status_id', PaymentStatusEnum::AUTHORISED)
            ->exists();

        if (! $hasAuthorisedPayment) {
            return;
        }

        $advisor = User::find($advisorId);
        if (! $advisor) {
            return;
        }

        // Get all users who should see updated counts: advisor + managers of advisor's teams
        $affectedUserIds = [$advisorId];

        // Get advisor's team IDs
        $advisorTeamIds = $advisor->getUserTeamIds();

        if (! empty($advisorTeamIds)) {
            // Get all managers who manage teams that the advisor belongs to
            $managerRoles = [
                RolesEnum::CarManager,
                RolesEnum::HealthManager,
                RolesEnum::TravelManager,
                RolesEnum::LifeManager,
                RolesEnum::HomeManager,
                RolesEnum::PetManager,
                RolesEnum::BikeManager,
                RolesEnum::CycleManager,
                RolesEnum::YachtManager,
                RolesEnum::JetskiManager,
                RolesEnum::BusinessManager,
            ];

            $managerUserIds = User::whereHas('roles', function ($query) use ($managerRoles) {
                $query->whereIn('name', $managerRoles);
            })
                ->whereHas('teams', function ($query) use ($advisorTeamIds) {
                    $query->whereIn('id', $advisorTeamIds);
                })
                ->pluck('id')
                ->toArray();

            $affectedUserIds = array_unique(array_merge($affectedUserIds, $managerUserIds));
        }

        // Calculate and broadcast count for each affected user
        $paymentRepository = app(PaymentRepository::class);

        foreach ($affectedUserIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $count = $paymentRepository->getAuthorisePaymentCount($user);
            event(new AuthorisedPaymentCountUpdated($userId, $count));
        }

        LoggerService::info('NotificationService - Broadcasted authorised payment count updates for quote: '.$model->uuid, [
            'affected_users' => $affectedUserIds,
        ]);
    }
}
