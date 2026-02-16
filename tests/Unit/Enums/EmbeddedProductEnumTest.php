<?php

declare(strict_types=1);

use App\Enums\EmbeddedProductEnum;
use App\Strategies\EmbeddedProducts\ECB;
use App\Strategies\EmbeddedProducts\EmbeddedProduct;

describe('EmbeddedProductEnum', function () {
    describe('CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS', function () {
        test('contains MDX and ECB', function () {
            expect(EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)
                ->toContain(EmbeddedProductEnum::MDX)
                ->toContain(EmbeddedProductEnum::ECB);
        });

        test('has exactly two allowed EPs for retargeting reminder', function () {
            expect(EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS)->toHaveCount(2);
        });
    });

    describe('getEpStrategyClass', function () {
        test('returns ECB class for ECB short code', function () {
            expect(EmbeddedProductEnum::getEpStrategyClass(EmbeddedProductEnum::ECB))
                ->toBe(ECB::class);
        });

        test('returns EmbeddedProduct class for MDX short code', function () {
            expect(EmbeddedProductEnum::getEpStrategyClass(EmbeddedProductEnum::MDX))
                ->toBe(EmbeddedProduct::class);
        });

        test('returns EmbeddedProduct class for COURIER short code', function () {
            expect(EmbeddedProductEnum::getEpStrategyClass(EmbeddedProductEnum::COURIER))
                ->toBe(EmbeddedProduct::class);
        });

        test('returns EmbeddedProduct class for unknown short code', function () {
            expect(EmbeddedProductEnum::getEpStrategyClass('UNKNOWN'))
                ->toBe(EmbeddedProduct::class);
        });
    });
});
