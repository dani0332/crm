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
