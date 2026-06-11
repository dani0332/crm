<?php

declare(strict_types=1);

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerMembers;
use App\Models\HealthQuote;
use App\Services\HealthQuoteService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
    $this->service = app(HealthQuoteService::class);
});

describe('HealthQuoteService::updateHealthData', function () {
    test('saves emirate_of_your_visa_id from request when entity', function () {
        $quote = HealthQuote::withoutEvents(fn () => HealthQuote::factory()->create([
            'emirate_of_your_visa_id' => 1,
            'gender' => 'M',
        ]));
        $request = (object) ['emirate_of_registration_id' => 5];

        HealthQuote::withoutEvents(function () use ($quote, $request) {
            $this->service->updateHealthData($quote, $request, true);
        });

        expect($quote->fresh()->emirate_of_your_visa_id)->toBe(5);
    });

    test('copies emirate_of_your_visa_id from principal member when individual', function () {
        $quote = HealthQuote::withoutEvents(fn () => HealthQuote::factory()->create([
            'emirate_of_your_visa_id' => 1,
            'gender' => 'M',
        ]));

        CustomerMembers::withoutEvents(fn () => CustomerMembers::unguarded(fn () => CustomerMembers::create([
            'quote_type' => HealthQuote::class,
            'quote_id' => $quote->id,
            'is_principal' => 1,
            'customer_type' => CustomerTypeEnum::Individual,
            'emirate_of_your_visa_id' => 7,
            'first_name' => 'Test',
            'last_name' => 'Member',
            'gender' => 'M',
            'nationality_id' => 1,
            'salary_band_id' => 1,
            'member_category_id' => 1,
            'is_insured' => 1,
            'is_policy_holder' => 0,
        ])));

        $request = (object) ['emirate_of_registration_id' => 99];

        HealthQuote::withoutEvents(function () use ($quote, $request) {
            $quote->load('activeMembers');
            $this->service->updateHealthData($quote, $request, false);
        });

        expect($quote->fresh()->emirate_of_your_visa_id)->toBe(7);
    });

    test('does not update emirate when no principal member exists for individual', function () {
        $quote = HealthQuote::withoutEvents(fn () => HealthQuote::factory()->create([
            'emirate_of_your_visa_id' => 3,
            'gender' => 'M',
        ]));
        $request = (object) ['emirate_of_registration_id' => 99];

        HealthQuote::withoutEvents(function () use ($quote, $request) {
            $quote->load('activeMembers');
            $this->service->updateHealthData($quote, $request, false);
        });

        expect($quote->fresh()->emirate_of_your_visa_id)->toBe(3);
    });

    test('does nothing and returns when quote is null', function () {
        expect(fn () => $this->service->updateHealthData(null, (object) ['emirate_of_registration_id' => 1], true))
            ->not->toThrow(Throwable::class);
    });
});
