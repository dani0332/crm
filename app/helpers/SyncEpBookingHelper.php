<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\SageEmbeddedProductEnum;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;

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
        $isBookingQueued = (int) $transaction?->sage_status_id === SageEmbeddedProductEnum::BOOKING_QUEUED->id();
        $isSageBookingCompleted = (int) $transaction?->sage_status_id === SageEmbeddedProductEnum::BOOKING_COMPLETED->id();
        $isSageBookingCancelled = (int) $transaction?->sage_status_id === SageEmbeddedProductEnum::BOOKING_CANCELLED->id();

        $isNotEligible = $isInvalidTransaction || $isCourierEp || ! $isPolicyBooked || ! $isReadyForSage || ! $isPaymentCaptured || $isBookingQueued || $isSageBookingCompleted || $isSageBookingCancelled;

        return ! $isNotEligible;
    }
}
