<?php

declare(strict_types=1);

use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypes;
use App\Models\InsuranceProvider;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use App\Support\AmlQuoteAutomation\AmlAutomatableLobRegistry;

test('skips api issuance check for Savings PersonalQuote with OIC provider', function () {
    $personalQuote = new PersonalQuote;
    $personalQuote->setRelation('insuranceProvider', new InsuranceProvider(['code' => InsuranceProvidersEnum::OIC]));

    expect(AmlAutomatableLobRegistry::skipsApiIssuanceStatusCheckForAutomatedAml(QuoteTypes::SAVINGS, $personalQuote))->toBeTrue();
});

test('does not skip for Savings when insurer is not OIC', function () {
    $personalQuote = new PersonalQuote;
    $personalQuote->setRelation('insuranceProvider', new InsuranceProvider(['code' => InsuranceProvidersEnum::ADNIC]));

    expect(AmlAutomatableLobRegistry::skipsApiIssuanceStatusCheckForAutomatedAml(QuoteTypes::SAVINGS, $personalQuote))->toBeFalse();
});

test('does not skip for non-Savings quote type', function () {
    $personalQuote = new PersonalQuote;
    $personalQuote->setRelation('insuranceProvider', new InsuranceProvider(['code' => InsuranceProvidersEnum::OIC]));

    expect(AmlAutomatableLobRegistry::skipsApiIssuanceStatusCheckForAutomatedAml(QuoteTypes::CYBER, $personalQuote))->toBeFalse();
});

test('does not skip when quote request is TravelQuote', function () {
    expect(AmlAutomatableLobRegistry::skipsApiIssuanceStatusCheckForAutomatedAml(QuoteTypes::SAVINGS, new TravelQuote))->toBeFalse();
});
