<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;
use App\Models\EmbeddedTransaction;
use App\Strategies\EmbeddedProducts\EmbeddedProduct;

describe('EmbeddedProduct', function () {
    describe('isDisabled', function () {
        test('returns true when preCheckEpTransactionIsDisabled is true (payment AUTHORISED)', function () {
            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::AUTHORISED;
            $epTransaction->is_active = 1;

            $strategy = new EmbeddedProduct;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });

        test('returns true when preCheckEpTransactionIsDisabled is true (payment CAPTURED)', function () {
            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::CAPTURED;
            $epTransaction->is_active = 1;

            $strategy = new EmbeddedProduct;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });

        test('returns true when is_active is 0', function () {
            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 0;

            $strategy = new EmbeddedProduct;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });

        test('returns false when draft and active', function () {
            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 1;

            $strategy = new EmbeddedProduct;

            expect($strategy->isDisabled($epTransaction))->toBeFalse();
        });
    });
});

afterEach(function () {
    Mockery::close();
});
