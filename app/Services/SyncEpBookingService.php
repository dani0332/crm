<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\helpers\SyncEpBookingHelper;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\SendsEpFailureEmail;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SyncEpBookingService extends BaseService
{
    use GenericQueriesAllLobs;
    use SendsEpFailureEmail;

    private const string LOG_PREFIX = 'SyncEpBookingService:';
    private const string UNEXPECTED_ERROR_MESSAGE = 'An unexpected error occurred while retrying Sage booking. Please try again or contact support.';

    public function __construct(
        protected SageApiEmbeddedProductService $sageApiEmbeddedProductService
    ) {
        parent::__construct();
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

            $message = self::UNEXPECTED_ERROR_MESSAGE;
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
        $context = $this->resolveValidatedContextOrFailure($data);

        if ($context['message'] !== null) {
            return ['ok' => false, 'message' => $context['message']];
        }

        $lockKey = 'ep-sync-sage-booking-'.$context['transaction']->id;
        if (! Cache::add($lockKey, true, now()->addMinutes(5))) {
            return ['ok' => false, 'message' => 'Sage booking retry is already in progress for this embedded product. Please wait and try again.'];
        }

        return [
            'ok' => true,
            'lockKey' => $lockKey,
            'quote' => $context['quote'],
            'transaction' => $context['transaction'],
            'ep' => $context['ep'],
            'quoteTypeId' => $context['quoteTypeId'],
        ];
    }

    /**
     * @param  array{quoteId: int, modelType: string, epTransactionId: int, insuranceProviderId: int}  $data
     * @return array{message: string, quote: null, transaction: null, ep: null, quoteTypeId: null}|array{message: null, quote: object, transaction: EmbeddedTransaction, ep: EmbeddedProduct, quoteTypeId: int}
     */
    private function resolveValidatedContextOrFailure(array $data): array
    {
        $quote = $this->getQuoteObject($data['modelType'], $data['quoteId']);
        $transaction = ! empty($quote) ? $this->findEmbeddedTransaction((int) $data['epTransactionId']) : null;
        $ep = $transaction?->product?->embeddedProduct;
        $quoteTypeId = QuoteTypes::getIdFromValue($data['modelType']);

        $message = $this->validateResolvedContext($data, $quote, $transaction, $ep, $quoteTypeId);

        return [
            'message' => $message,
            'quote' => $message === null ? $quote : null,
            'transaction' => $message === null ? $transaction : null,
            'ep' => $message === null ? $ep : null,
            'quoteTypeId' => $message === null ? $quoteTypeId : null,
        ];
    }

    private function findEmbeddedTransaction(int $epTransactionId): ?EmbeddedTransaction
    {
        return EmbeddedTransaction::query()
            ->with('product.embeddedProduct')
            ->whereKey($epTransactionId)
            ->first();
    }

    /**
     * @param  array{quoteId: int, modelType: string, epTransactionId: int, insuranceProviderId: int}  $data
     */
    private function validateResolvedContext(
        array $data,
        mixed $quote,
        ?EmbeddedTransaction $transaction,
        ?EmbeddedProduct $ep,
        ?int $quoteTypeId,
    ): ?string {
        return match (true) {
            empty($quote) => 'Quote not found',
            ! $transaction => 'Embedded transaction not found',
            ! $ep => 'Embedded product not found',
            ! SyncEpBookingHelper::isTransactionEligibleForManualSageBookingRetry($transaction, $quote, $ep) => 'This embedded product is not eligible for Sage booking retry.',
            $this->syncEpBookingInsuranceProviderMismatch($ep, (int) $data['insuranceProviderId']) => 'Insurance provider mismatch.',
            $quoteTypeId === null => 'Invalid quote type.',
            $this->embeddedTransactionDoesNotMatchQuote($transaction, $quoteTypeId, $quote) => 'Transaction does not match the selected quote.',
            default => null,
        };
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
