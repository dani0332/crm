<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Helpers\SyncEpBookingHelper;
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
        $message = $this->syncEpBookingUnauthorizedMessage();
        $quote = null;
        $transaction = null;
        $ep = null;
        $quoteTypeId = null;

        if ($message === null) {
            $quote = $this->getQuoteObject($data['modelType'], $data['quoteId']);
            if (empty($quote)) {
                $message = 'Quote not found';
            }
        }

        if ($message === null) {
            $transaction = EmbeddedTransaction::query()
                ->with('product.embeddedProduct')
                ->whereKey($data['epTransactionId'])
                ->first();

            if (! $transaction) {
                $message = 'Embedded transaction not found';
            }
        }

        if ($message === null) {
            $ep = $transaction->product?->embeddedProduct;
            if (! $ep) {
                $message = 'Embedded product not found';
            }
        }

        if ($message === null && ! SyncEpBookingHelper::isTransactionEligibleForManualSageBookingRetry($transaction, $quote, $ep)) {
            $message = 'This embedded product is not eligible for Sage booking retry.';
        }

        if ($message === null && $this->syncEpBookingInsuranceProviderMismatch($ep, (int) $data['insuranceProviderId'])) {
            $message = 'Insurance provider mismatch.';
        }

        if ($message === null) {
            $quoteTypeId = QuoteTypes::getIdFromValue($data['modelType']);
            if ($quoteTypeId === null) {
                $message = 'Invalid quote type.';
            }
        }

        if ($message === null && $this->embeddedTransactionDoesNotMatchQuote($transaction, $quoteTypeId, $quote)) {
            $message = 'Transaction does not match the selected quote.';
        }

        if ($message !== null) {
            return ['ok' => false, 'message' => $message];
        }

        $lockKey = 'ep-sync-sage-booking-'.$transaction->id;
        if (! Cache::add($lockKey, true, now()->addMinutes(5))) {
            return ['ok' => false, 'message' => 'Sage booking retry is already in progress for this embedded product. Please wait and try again.'];
        }

        return [
            'ok' => true,
            'lockKey' => $lockKey,
            'quote' => $quote,
            'transaction' => $transaction,
            'ep' => $ep,
            'quoteTypeId' => $quoteTypeId,
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
