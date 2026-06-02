<?php

declare(strict_types=1);

use App\Enums\GenericRequestEnum;
use App\Enums\HealthQuoteUaePassApiStatus;

describe('HealthQuoteUaePassApiStatus', function () {
    test('displayLabel returns null for empty', function () {
        expect(HealthQuoteUaePassApiStatus::displayLabel(null))->toBe(null);
        expect(HealthQuoteUaePassApiStatus::displayLabel(''))->toBe(null);
    });

    test('isStoredValue accepts persisted enum values only', function () {
        expect(HealthQuoteUaePassApiStatus::isStoredValue(HealthQuoteUaePassApiStatus::DocSigned->value))->toBeTrue();
        expect(HealthQuoteUaePassApiStatus::isStoredValue('invalid'))->toBeFalse();
        expect(HealthQuoteUaePassApiStatus::isStoredValue(null))->toBeFalse();
    });

    test('displayLabel maps stored values to UAE PASS labels', function () {
        expect(HealthQuoteUaePassApiStatus::displayLabel(HealthQuoteUaePassApiStatus::AuthenticationSuccess->value))
            ->toBe('UAE PASS – Authentication Success');
        expect(HealthQuoteUaePassApiStatus::displayLabel(HealthQuoteUaePassApiStatus::DocsNotReceived->value))
            ->toBe('UAE PASS – Docs not Received');
    });

    test('filterDropdown includes All', function () {
        $options = HealthQuoteUaePassApiStatus::filterDropdown();
        expect($options[0])->toBe([
            'value' => GenericRequestEnum::ALL,
            'label' => 'All',
        ]);
    });

    test('ALL sentinel is not a stored database value', function () {
        expect(HealthQuoteUaePassApiStatus::isStoredValue(GenericRequestEnum::ALL))->toBeFalse();
    });
});
