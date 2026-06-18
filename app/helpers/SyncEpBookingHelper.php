<?php

declare(strict_types=1);

namespace App\helpers;

use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\SageEmbeddedProductEnum;
use App\Enums\SageEnum;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;
use App\Models\SageProcess;

class SyncEpBookingHelper
{
    /**
     * Eligibility for manual "Sync EP Booking" retry (EP Admin, all API-integrated EP LOBs except courier).
     */
    public static function isTransactionEligibleForManualSageBookingRetry(?EmbeddedTransaction $transaction, mixed $quote, ?EmbeddedProduct $ep): bool
    {
        $isInvalidTransaction = ! $transaction || ! $quote || ! $ep;
        $isCourierEp = $ep?->short_code === EmbeddedProductEnum::COURIER;
        $isPolicyBooked = (int) $quote?->quote_status_id === QuoteStatusEnum::PolicyBooked;
        $isReadyForSage = $transaction?->policy_status === EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE;
        $isPaymentCaptured = (int) $transaction?->payment_status_id === PaymentStatusEnum::CAPTURED;
        $isSageBookingCompleted = (int) $transaction?->sage_status_id === SageEmbeddedProductEnum::BOOKING_COMPLETED->id();
        $isSageBookingCancelled = (int) $transaction?->sage_status_id === SageEmbeddedProductEnum::BOOKING_CANCELLED->id();

        $isNotEligible = $isInvalidTransaction || $isCourierEp || ! $isPolicyBooked || ! $isReadyForSage || ! $isPaymentCaptured || $isSageBookingCompleted || $isSageBookingCancelled;

        return ! $isNotEligible;
    }

    public static function hasActiveSageProcess(EmbeddedTransaction $transaction): bool
    {
        $sageProcess = SageProcess::query()
            ->where('model_type', $transaction::class)
            ->where('model_id', $transaction->id)
            ->first();

        return $sageProcess !== null && $sageProcess->status !== SageEnum::SAGE_PROCESS_FAILED_STATUS;
    }
}
