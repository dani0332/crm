<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\EmbeddedTransaction;
use App\Strategies\EmbeddedProducts\EmbeddedProduct;

describe('EmbeddedProduct', function () {
    describe('isCriteriaMatched', function () {
        test('defaults to true for any quote value', function () {
            $strategy = new EmbeddedProduct;
            $method = new ReflectionMethod(EmbeddedProduct::class, 'isCriteriaMatched');
            $method->setAccessible(true);

            expect($method->invoke($strategy, null))->toBeTrue();
            expect($method->invoke($strategy, ''))->toBeTrue();
            expect($method->invoke($strategy, (object) ['id' => 1]))->toBeTrue();
        });
    });

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

        test('returns true when is_active is integer 0', function () {
            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = 0;

            $strategy = new EmbeddedProduct;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });

        test('returns true when is_active is string "0" (as returned by MySQL without a cast)', function () {
            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = '0';

            $strategy = new EmbeddedProduct;

            expect($strategy->isDisabled($epTransaction))->toBeTrue();
        });

        test('returns true when is_active is boolean false', function () {
            $epTransaction = Mockery::mock(EmbeddedTransaction::class)->makePartial();
            $epTransaction->payment_status_id = PaymentStatusEnum::DRAFT;
            $epTransaction->is_active = false;

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

    describe('applyLobFilterToEmbeddedReportQuery', function () {
        test('includes rows with null quote_type_id when code matches Courier COU ref segment', function () {
            $strategy = new EmbeddedProduct;
            $method = new ReflectionMethod(EmbeddedProduct::class, 'applyLobFilterToEmbeddedReportQuery');
            $method->setAccessible(true);

            $query = EmbeddedTransaction::query();
            $method->invoke($strategy, $query, [QuoteTypeId::Car]);

            $sql = strtolower($query->toSql());
            expect($sql)->toContain('quote_type_id');
            expect($sql)->toContain('is null');
            expect($sql)->toContain('lower(');
            expect($sql)->toContain('like ?');
            expect($query->getBindings())->toContain('cou-car-%');
        });
    });
});

afterEach(function () {
    Mockery::close();
});
