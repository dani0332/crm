<?php

use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\SageEmbeddedProductEnum;
use App\Helpers\SyncEpBookingHelper;
use App\Models\CarQuote;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

it('returns false when transaction, quote, or embedded product is missing', function (): void {
    $quote = CarQuote::factory()->createOneQuietly();
    $ep = EmbeddedProduct::factory()->mdx()->createOneQuietly();
    $transaction = EmbeddedTransaction::factory()->createOneQuietly();

    expect(SyncEpBookingHelper::isTransactionEligibleForManualSageBookingRetry(null, $quote, $ep))->toBeFalse()
        ->and(SyncEpBookingHelper::isTransactionEligibleForManualSageBookingRetry($transaction, null, $ep))->toBeFalse()
        ->and(SyncEpBookingHelper::isTransactionEligibleForManualSageBookingRetry($transaction, $quote, null))->toBeFalse();
});

it('returns true when all manual sage booking retry criteria are met', function (): void {
    $ep = EmbeddedProduct::factory()->mdx()->createOneQuietly();
    $quote = CarQuote::factory()->createOneQuietly([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
    ]);
    $transaction = EmbeddedTransaction::factory()->createOneQuietly([
        'policy_status' => EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE,
        'payment_status_id' => PaymentStatusEnum::CAPTURED,
        'sage_status_id' => null,
    ]);

    expect(SyncEpBookingHelper::isTransactionEligibleForManualSageBookingRetry($transaction, $quote, $ep))->toBeTrue();
});

it('returns false for courier embedded product', function (): void {
    $ep = EmbeddedProduct::factory()->cou()->createOneQuietly();
    $quote = CarQuote::factory()->createOneQuietly([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
    ]);
    $transaction = EmbeddedTransaction::factory()->createOneQuietly([
        'policy_status' => EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE,
        'payment_status_id' => PaymentStatusEnum::CAPTURED,
        'sage_status_id' => null,
    ]);

    expect($ep->short_code)->toBe(EmbeddedProductEnum::COURIER)
        ->and(SyncEpBookingHelper::isTransactionEligibleForManualSageBookingRetry($transaction, $quote, $ep))->toBeFalse();
});

it('returns false when sage booking is queued, completed, or cancelled', function (int $sageStatusId): void {
    $ep = EmbeddedProduct::factory()->mdx()->createOneQuietly();
    $quote = CarQuote::factory()->createOneQuietly([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
    ]);
    $transaction = EmbeddedTransaction::factory()->createOneQuietly([
        'policy_status' => EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE,
        'payment_status_id' => PaymentStatusEnum::CAPTURED,
        'sage_status_id' => $sageStatusId,
    ]);

    expect(SyncEpBookingHelper::isTransactionEligibleForManualSageBookingRetry($transaction, $quote, $ep))->toBeFalse();
})->with([
    'queued' => SageEmbeddedProductEnum::BOOKING_QUEUED->id(),
    'completed' => SageEmbeddedProductEnum::BOOKING_COMPLETED->id(),
    'cancelled' => SageEmbeddedProductEnum::BOOKING_CANCELLED->id(),
]);
