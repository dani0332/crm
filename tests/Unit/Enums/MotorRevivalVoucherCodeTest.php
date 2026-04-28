<?php

declare(strict_types=1);

use App\Enums\MotorRevivalVoucherCode;

describe('MotorRevivalVoucherCode', function () {
    test('TrialSevenDay prefix matches MACRM motor revival voucher scheme', function () {
        expect(MotorRevivalVoucherCode::TrialSevenDay->value)->toBe('MA_FREE7_');
    });

    test('codeForQuoteUuid uppercases uuid and prepends prefix', function () {
        expect(MotorRevivalVoucherCode::TrialSevenDay->codeForQuoteUuid('ab12-cd34'))
            ->toBe('MA_FREE7_AB12-CD34');
    });
});
