<?php

declare(strict_types=1);

use App\Enums\GenericRequestEnum;
use App\Enums\HealthQuoteDigitalSignatory;

describe('HealthQuoteDigitalSignatory', function () {
    test('filterDropdown first option uses GenericRequestEnum::ALL', function () {
        $options = HealthQuoteDigitalSignatory::filterDropdown();
        expect($options[0])->toBe([
            'value' => GenericRequestEnum::ALL,
            'label' => 'All',
        ]);
    });

    test('ALL sentinel is not a stored database value', function () {
        expect(HealthQuoteDigitalSignatory::isStoredValue(GenericRequestEnum::ALL))->toBeFalse();
    });
});
