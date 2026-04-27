<?php

declare(strict_types=1);

use App\Models\HealthQuote;
use App\Services\HealthQuoteService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
    $this->service = app(HealthQuoteService::class);
});

describe('HealthQuoteService::updateHealthEntityData', function () {
    test('saves emirate_of_your_visa_id from request', function () {
        $quote = HealthQuote::withoutEvents(fn () => HealthQuote::factory()->create([
            'emirate_of_your_visa_id' => 1,
            'gender' => 'M',
        ]));
        $request = (object) ['emirate_of_registration_id' => 5];

        HealthQuote::withoutEvents(function () use ($quote, $request) {
            $this->service->updateHealthEntityData($quote, $request);
        });

        expect($quote->fresh()->emirate_of_your_visa_id)->toBe(5);
    });

    test('clears revamp migration fields so lead can be re-migrated', function () {
        $quote = HealthQuote::withoutEvents(fn () => HealthQuote::factory()->create([
            'member_category_id' => 20,
            'policy_holder_category_code' => 'RESIDENT',
            'visa_category_id' => 4,
            'gender' => 'M',
            'marital_status_id' => 1,
            'salary_band_id' => 3,
            'insure_code' => 'ONLY_MYSELF',
            'policy_holder_code' => 'ME',
        ]));
        $request = (object) ['emirate_of_registration_id' => 2];

        HealthQuote::withoutEvents(function () use ($quote, $request) {
            $this->service->updateHealthEntityData($quote, $request);
        });

        $fresh = $quote->fresh();
        expect($fresh->member_category_id)->toBeNull()
            ->and($fresh->policy_holder_category_code)->toBeNull()
            ->and($fresh->visa_category_id)->toBeNull()
            ->and($fresh->gender)->toBeNull()
            ->and($fresh->marital_status_id)->toBeNull()
            ->and($fresh->salary_band_id)->toBeNull()
            ->and($fresh->insure_code)->toBeNull()
            ->and($fresh->policy_holder_code)->toBeNull();
    });

    test('does nothing and returns when quote is null', function () {
        expect(fn () => $this->service->updateHealthEntityData(null, (object) ['emirate_of_registration_id' => 1]))
            ->not->toThrow(Throwable::class);
    });
});
