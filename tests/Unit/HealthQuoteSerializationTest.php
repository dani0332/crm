<?php

use App\Models\HealthQuote;

it('does not include api issuance appends by default so list views avoid resolving PolicyIssuanceService per row', function () {
    $quote = new HealthQuote;

    expect($quote->getAppends())->not->toContain('api_issuance_status', 'insurer_api_status');
});

it('can append api issuance attributes for detail views when needed', function () {
    $quote = new HealthQuote;
    $quote->append(['api_issuance_status', 'insurer_api_status']);

    expect($quote->getAppends())->toContain('api_issuance_status', 'insurer_api_status');
});
