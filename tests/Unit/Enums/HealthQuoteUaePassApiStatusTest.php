<?php

declare(strict_types=1);

use App\Enums\HealthQuoteUaePassApiStatus;

describe('HealthQuoteUaePassApiStatus', function () {
    test('displayLabel returns em dash for empty', function () {
        expect(HealthQuoteUaePassApiStatus::displayLabel(null))->toBe('—');
        expect(HealthQuoteUaePassApiStatus::displayLabel(''))->toBe('—');
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
            'value' => HealthQuoteUaePassApiStatus::FILTER_ALL->value,
            'label' => 'All',
        ]);
    });

    test('FILTER_ALL is not a stored database value', function () {
        expect(HealthQuoteUaePassApiStatus::isStoredValue(HealthQuoteUaePassApiStatus::FILTER_ALL->value))->toBeFalse();
    });
});
