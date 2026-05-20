<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;

test('cancelled declined and failed statuses are grouped for travel member and payment deletion flows', function () {
    expect(PaymentStatusEnum::getCancelledDeclinedOrFailedStatuses())->toBe([
        PaymentStatusEnum::CANCELLED,
        PaymentStatusEnum::DECLINED,
        PaymentStatusEnum::FAILED,
    ]);
});

test('authorized payment statuses are grouped for travel member delete guard', function () {
    expect(PaymentStatusEnum::getConfirmedOrSettledPaymentStatuses())->toBe([
        PaymentStatusEnum::AUTHORISED,
        PaymentStatusEnum::PAID,
        PaymentStatusEnum::CAPTURED,
        PaymentStatusEnum::PARTIAL_CAPTURED,
        PaymentStatusEnum::PARTIALLY_PAID,
    ]);
});
