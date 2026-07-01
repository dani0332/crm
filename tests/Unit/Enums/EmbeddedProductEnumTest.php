<?php

declare(strict_types=1);

use App\Enums\EmbeddedProductEnum;

describe('EmbeddedProductEnum', function () {
    describe('CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS', function () {
        test('contains MDX and ECB', function () {
            expect(EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
                ->toContain(EmbeddedProductEnum::MDX)
                ->toContain(EmbeddedProductEnum::ECB)
                ->toContain(EmbeddedProductEnum::RDX);
        });

        test('has exactly two allowed EPs for retargeting reminder', function () {
            expect(EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)->toHaveCount(3);
        });
    });
});
