<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SageEmbeddedProductEnum;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\SendsEpFailureEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SyncEpBookingService extends BaseService
{
    use GenericQueriesAllLobs;
    use SendsEpFailureEmail;

    private const string LOG_PREFIX = 'SyncEpBookingService:';

    public function __construct(
        protected SageApiEmbeddedProductService $sageApiEmbeddedProductService
    ) {
        parent::__construct();
    }

    /**
     * Eligibility for manual "Sync EP Booking" retry (EP Admin, all API-integrated EP LOBs except courier).
     */
    public function isTransactionEligibleForManualSageBookingRetry(?EmbeddedTransaction $transaction, mixed $quote, ?EmbeddedProduct $ep): bool
    {
        $isInvalidTransaction = ! $transaction || ! $quote || ! $ep;
        $isCourierEp = $ep?->short_code === EmbeddedProductEnum::COURIER;
        $isPolicyBooked = (int) $quote?->quote_status_id === QuoteStatusEnum::PolicyBooked;
        $isReadyForSage = $transaction?->policy_status === EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE;
        $isPaymentCaptured = (int) $transaction?->payment_status_id === PaymentStatusEnum::CAPTURED;
        $isBookingQueued = (int) $transaction?->sage_status_id === SageEmbeddedProductEnum::BOOKING_QUEUED->id();
        $isSageBookingCompleted = (int) $transaction?->sage_status_id === SageEmbeddedProductEnum::BOOKING_COMPLETED->id();
        $isSageBookingCancelled = (int) $transaction?->sage_status_id === SageEmbeddedProductEnum::BOOKING_CANCELLED->id();

        $isNotEligible = $isInvalidTransaction || $isCourierEp || ! $isPolicyBooked || ! $isReadyForSage || ! $isPaymentCaptured || $isBookingQueued || $isSageBookingCompleted || $isSageBookingCancelled;

        if ($isNotEligible) {
            return false;
        }

        return true;
    }

    /**
     * Manual Sage EP booking retry from IMCRM (EP Admin only).
     *
     * @param  array{quoteId: int, modelType: string, epTransactionId: int, insuranceProviderId: int}  $data
     * @return array{success: bool, message: string}
     */
    public function process(array $data): array
    {
        $logPrefix = self::LOG_PREFIX.' ';
        $resolved = $this->resolveProcessContextOrFailure($data);

        if (! $resolved['ok']) {
            return ['success' => false, 'message' => $resolved['message']];
        }

        $success = false;
        $message = '';

        try {
            LoggerService::startQuoteLogging($resolved['quote']->code, LoggerFeatureEnum::EP_PROCESS_SYNC_EP_BOOKING);

            $request = [
                'epTransactionId' => $resolved['transaction']->id,
                'insuranceProviderId' => (int) $data['insuranceProviderId'],
                'modelType' => $data['modelType'],
                'quoteId' => $resolved['quote']->id,
                'isManualEpBookingRetry' => true,
            ];

            $result = $this->sageApiEmbeddedProductService->scheduleBookingOfEmbeddedProduct($request);

            if (! ($result['status'] ?? false)) {
                $this->sendEpFailureEmail((int) $resolved['quote']->id, (int) $resolved['quoteTypeId'], (int) $resolved['transaction']->id, $logPrefix, true);
            }

            $success = (bool) ($result['status'] ?? false);
            $message = (string) ($result['message'] ?? 'Sage booking retry completed.');
        } catch (Throwable $e) {
            LoggerService::error($logPrefix.$e->getMessage(), extra: ['exception' => $e]);
            $this->sendEpFailureEmail((int) $resolved['quote']->id, (int) $resolved['quoteTypeId'], (int) $resolved['transaction']->id, $logPrefix, true);

            $message = $e->getMessage();
        } finally {
            Cache::forget($resolved['lockKey']);
        }

        return ['success' => $success, 'message' => $message];
    }

    /**
     * Validates input, loads related models, acquires the sync lock, or returns a failure message.
     *
     * @param  array{quoteId: int, modelType: string, epTransactionId: int, insuranceProviderId: int}  $data
     * @return array{ok: true, lockKey: string, quote: object, transaction: EmbeddedTransaction, ep: EmbeddedProduct, quoteTypeId: int}|array{ok: false, message: string}
     */
    private function resolveProcessContextOrFailure(array $data): array
    {
        $validated = $this->buildSyncEpBookingValidatedContext($data);
        if ($validated['message'] !== null) {
            return ['ok' => false, 'message' => $validated['message']];
        }

        $lockKey = 'ep-sync-sage-booking-'.$validated['transaction']->id;
        if (! Cache::add($lockKey, true, now()->addMinutes(5))) {
            return ['ok' => false, 'message' => 'Sage booking retry is already in progress for this embedded product. Please wait and try again.'];
        }

        return [
            'ok' => true,
            'lockKey' => $lockKey,
            'quote' => $validated['quote'],
            'transaction' => $validated['transaction'],
            'ep' => $validated['ep'],
            'quoteTypeId' => $validated['quoteTypeId'],
        ];
    }

    /**
     * @param  array{quoteId: int, modelType: string, epTransactionId: int, insuranceProviderId: int}  $data
     * @return array{message: string}|array{message: null, quote: object, transaction: EmbeddedTransaction, ep: EmbeddedProduct, quoteTypeId: int}
     */
    private function buildSyncEpBookingValidatedContext(array $data): array
    {
        $payload = $this->resolveSyncEpBookingValidatedContextPayload($data);

        return $payload['message'] !== null
            ? ['message' => $payload['message']]
            : [
                'message' => null,
                'quote' => $payload['quote'],
                'transaction' => $payload['transaction'],
                'ep' => $payload['ep'],
                'quoteTypeId' => $payload['quoteTypeId'],
            ];
    }

    /**
     * Loads models and runs validation checks in order; on failure the array contains only a string message.
     *
     * @param  array{quoteId: int, modelType: string, epTransactionId: int, insuranceProviderId: int}  $data
     * @return array{message: string}|array{message: null, quote: object, transaction: EmbeddedTransaction, ep: EmbeddedProduct, quoteTypeId: int}
     */
    private function resolveSyncEpBookingValidatedContextPayload(array $data): array
    {
        $payload = $this->buildSyncEpBookingValidatedContextPayload($data);

        if ($payload['message'] !== null) {
            return ['message' => $payload['message']];
        }

        return [
            'message' => null,
            'quote' => $payload['quote'],
            'transaction' => $payload['transaction'],
            'ep' => $payload['ep'],
            'quoteTypeId' => $payload['quoteTypeId'],
        ];
    }

    /**
     * @param  array{quoteId: int, modelType: string, epTransactionId: int, insuranceProviderId: int}  $data
     * @return array{message: string, quote: null, transaction: null, ep: null, quoteTypeId: null}|array{message: null, quote: object, transaction: EmbeddedTransaction, ep: EmbeddedProduct, quoteTypeId: int}
     */
    private function buildSyncEpBookingValidatedContextPayload(array $data): array
    {
        if ($message = $this->syncEpBookingUnauthorizedMessage()) {
            return $this->syncEpBookingValidatedContextFailure($message);
        }

        $quote = $this->getQuoteObject($data['modelType'], $data['quoteId']);
        if (empty($quote)) {
            return $this->syncEpBookingValidatedContextFailure('Quote not found');
        }

        $transaction = EmbeddedTransaction::query()
            ->with('product.embeddedProduct')
            ->whereKey($data['epTransactionId'])
            ->first();

        if (! $transaction) {
            return $this->syncEpBookingValidatedContextFailure('Embedded transaction not found');
        }

        $ep = $transaction->product?->embeddedProduct;
        if (! $ep) {
            return $this->syncEpBookingValidatedContextFailure('Embedded product not found');
        }

        return $this->finalizeSyncEpBookingValidatedContextPayload($data, $quote, $transaction, $ep);
    }

    /**
     * @param  array{quoteId: int, modelType: string, epTransactionId: int, insuranceProviderId: int}  $data
     * @return array{message: string, quote: null, transaction: null, ep: null, quoteTypeId: null}|array{message: null, quote: object, transaction: EmbeddedTransaction, ep: EmbeddedProduct, quoteTypeId: int}
     */
    private function finalizeSyncEpBookingValidatedContextPayload(
        array $data,
        object $quote,
        EmbeddedTransaction $transaction,
        EmbeddedProduct $ep,
    ): array {
        if (! $this->isTransactionEligibleForManualSageBookingRetry($transaction, $quote, $ep)) {
            return $this->syncEpBookingValidatedContextFailure('This embedded product is not eligible for Sage booking retry.');
        }

        if ($this->syncEpBookingInsuranceProviderMismatch($ep, (int) $data['insuranceProviderId'])) {
            return $this->syncEpBookingValidatedContextFailure('Insurance provider mismatch.');
        }

        $quoteTypeId = QuoteTypes::getIdFromValue($data['modelType']);
        if ($quoteTypeId === null) {
            return $this->syncEpBookingValidatedContextFailure('Invalid quote type.');
        }

        if ($this->embeddedTransactionDoesNotMatchQuote($transaction, $quoteTypeId, $quote)) {
            return $this->syncEpBookingValidatedContextFailure('Transaction does not match the selected quote.');
        }

        return [
            'message' => null,
            'quote' => $quote,
            'transaction' => $transaction,
            'ep' => $ep,
            'quoteTypeId' => $quoteTypeId,
        ];
    }

    /**
     * @return array{message: string, quote: null, transaction: null, ep: null, quoteTypeId: null}
     */
    private function syncEpBookingValidatedContextFailure(string $message): array
    {
        return [
            'message' => $message,
            'quote' => null,
            'transaction' => null,
            'ep' => null,
            'quoteTypeId' => null,
        ];
    }

    private function syncEpBookingUnauthorizedMessage(): ?string
    {
        if (! Auth::user()?->can(PermissionsEnum::EMBEDDED_PRODUCT_SYNC_EP_BOOKING)) {
            return 'You are not authorized to perform this action.';
        }

        return null;
    }

    private function syncEpBookingInsuranceProviderMismatch(EmbeddedProduct $ep, int $insuranceProviderId): bool
    {
        return (int) $ep->insurance_provider_id !== $insuranceProviderId;
    }

    private function embeddedTransactionDoesNotMatchQuote(EmbeddedTransaction $transaction, int $quoteTypeId, mixed $quote): bool
    {
        return (int) $transaction->quote_type_id !== $quoteTypeId
            || (int) $transaction->quote_request_id !== (int) $quote->id;
    }
}
