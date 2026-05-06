<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;

beforeEach(function () {
    config([
        'constants.IMCRM_BASIC_AUTH_USER_NAME' => 'imcrm-test-user',
        'constants.IMCRM_BASIC_AUTH_PASSWORD' => 'imcrm-test-pass',
    ]);
});

it('requires quoteType for IMCRM automate AML screening', function () {
    $response = $this->postJson(
        '/api/v1/imcrm/quotes/automate-aml-screening',
        ['quoteUuid' => 'ABC123'],
        ['Authorization' => 'Basic '.base64_encode('imcrm-test-user:imcrm-test-pass')]
    );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['quoteType']);
});

it('rejects quoteType that is not in the automatable LOB registry', function () {
    $response = $this->postJson(
        '/api/v1/imcrm/quotes/automate-aml-screening',
        [
            'quoteUuid' => 'ABC123',
            'quoteType' => QuoteTypes::CAR->value,
        ],
        ['Authorization' => 'Basic '.base64_encode('imcrm-test-user:imcrm-test-pass')]
    );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['quoteType']);
});

it('returns 401 without IMCRM basic auth for automate AML screening', function () {
    $this->postJson('/api/v1/imcrm/quotes/automate-aml-screening', [
        'quoteUuid' => 'ABC123',
        'quoteType' => QuoteTypes::SAVINGS->value,
    ])->assertStatus(401);
});
