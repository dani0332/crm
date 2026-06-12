<?php

declare(strict_types=1);

use App\Enums\EmbeddedProductEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Repositories\EmbeddedProductRepository;

describe('EmbeddedProductRepository lineOfBusinessLabelFromCourierEpCode', function () {
    test('maps COU-CAR ref to Car', function () {
        expect(EmbeddedProductRepository::lineOfBusinessLabelFromCourierEpCode('COU-CAR-abc-123'))->toBe('Car');
    });

    test('maps COU-TRA ref to Travel', function () {
        expect(EmbeddedProductRepository::lineOfBusinessLabelFromCourierEpCode('COU-TRA-xyz'))->toBe('Travel');
    });

    test('maps COU-HOM ref to Home', function () {
        expect(EmbeddedProductRepository::lineOfBusinessLabelFromCourierEpCode('COU-HOM-uuid'))->toBe('Home');
    });

    test('maps COU-HAM ref to Home Appliances', function () {
        expect(EmbeddedProductRepository::lineOfBusinessLabelFromCourierEpCode('COU-HAM-uuid'))->toBe('Home Appliances');
    });

    test('maps COU-CYB ref to Cyber', function () {
        expect(EmbeddedProductRepository::lineOfBusinessLabelFromCourierEpCode('COU-CYB-uuid'))->toBe('Cyber');
    });

    test('returns null for non-Courier ref', function () {
        expect(EmbeddedProductRepository::lineOfBusinessLabelFromCourierEpCode('MDX-CAR-uuid'))->toBeNull();
    });
});

describe('EmbeddedProductRepository LOB filter helpers', function () {
    test('allowedLobCodesForEmbeddedProduct matches courier short code case-insensitively', function () {
        expect(EmbeddedProductRepository::allowedLobCodesForEmbeddedProduct('cou'))->not->toBeNull();
        expect(EmbeddedProductRepository::allowedLobCodesForEmbeddedProduct('COU'))->not->toBeNull();
    });

    test('resolveLobFilterQuoteTypeIds returns null when ep does not support multi-LOB filter', function () {
        expect(EmbeddedProductRepository::resolveLobFilterQuoteTypeIds(EmbeddedProductEnum::ECB, [quoteTypeCode::Car]))->toBeNull();
    });

    test('resolveLobFilterQuoteTypeIds returns null when selection is empty', function () {
        expect(EmbeddedProductRepository::resolveLobFilterQuoteTypeIds(EmbeddedProductEnum::COURIER, []))->toBeNull();
    });

    test('resolveLobFilterQuoteTypeIds returns empty list when no codes intersect allowed', function () {
        expect(EmbeddedProductRepository::resolveLobFilterQuoteTypeIds(EmbeddedProductEnum::COURIER, ['Health']))->toBe([]);
    });

    test('resolveLobFilterQuoteTypeIds maps selected allowed codes to quote type ids', function () {
        $ids = EmbeddedProductRepository::resolveLobFilterQuoteTypeIds(
            EmbeddedProductEnum::COURIER,
            [quoteTypeCode::Car, quoteTypeCode::Travel, quoteTypeCode::CYBER],
        );

        expect($ids)->toBeArray();
        expect($ids)->toHaveCount(3);
        expect($ids)->toContain(QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Cyber);
    });

    test('resolveLobFilterQuoteTypeIds works when courier short code casing differs', function () {
        $ids = EmbeddedProductRepository::resolveLobFilterQuoteTypeIds('cou', [quoteTypeCode::Car]);

        expect($ids)->toBeArray();
        expect($ids)->toContain(QuoteTypeId::Car);
    });

    test('courierEpRefSegmentForQuoteTypeFilter maps quote type ids to COU ref segments', function () {
        expect(EmbeddedProductRepository::courierEpRefSegmentForQuoteTypeFilter(QuoteTypeId::Car))->toBe('CAR');
        expect(EmbeddedProductRepository::courierEpRefSegmentForQuoteTypeFilter(QuoteTypeId::Home))->toBe('HOM');
        expect(EmbeddedProductRepository::courierEpRefSegmentForQuoteTypeFilter(QuoteTypeId::Travel))->toBe('TRA');
        expect(EmbeddedProductRepository::courierEpRefSegmentForQuoteTypeFilter(QuoteTypeId::Cyber))->toBe('CYB');
        expect(EmbeddedProductRepository::courierEpRefSegmentForQuoteTypeFilter(999))->toBeNull();
    });
});
