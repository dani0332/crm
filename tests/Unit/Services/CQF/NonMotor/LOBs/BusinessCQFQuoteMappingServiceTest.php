<?php

declare(strict_types=1);

use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\LOBs\BusinessCQFQuoteMappingService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->service = (new ReflectionClass(BusinessCQFQuoteMappingService::class))->newInstanceWithoutConstructor();
});

it('includes product_type from businessTypeOfInsurance text in failed quote data', function () {
    $businessTypeOfInsurance = (object) ['text' => 'Property'];
    $businessQuote = (object) ['businessTypeOfInsurance' => $businessTypeOfInsurance, 'payments' => collect()];

    $quote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $quote->shouldReceive('loadMissing')->andReturnSelf();
    $quote->shouldReceive('getAttribute')->andReturnUsing(function ($key) use ($businessQuote) {
        return match ($key) {
            'first_name' => 'Corp',
            'last_name' => 'Client',
            'email' => 'corp@example.com',
            'mobile_no' => '+971501234567',
            'policy_number' => 'BUS-POL-001',
            'policy_start_date' => now()->subDays(365),
            'policy_expiry_date' => now()->addDays(30),
            'businessQuote' => $businessQuote,
            'payments' => collect(),
            default => null,
        };
    });
    $quote->shouldReceive('getRelationValue')->with('insuranceProvider')->andReturn(null);
    $quote->shouldReceive('getRelationValue')->with('currentlyInsuredWith')->andReturn(null);
    $quote->shouldReceive('getRelationValue')->with('advisor')->andReturn(null);
    $quote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($businessQuote);

    $failed = $this->service->mapFailedQuoteData($quote);

    expect($failed)->toHaveKey('product_type')
        ->and($failed['product_type'])->toBe('Property');
});

it('sets product_type to null when businessQuote has no businessTypeOfInsurance', function () {
    $businessQuote = (object) ['businessTypeOfInsurance' => null, 'payments' => collect()];

    $quote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $quote->shouldReceive('loadMissing')->andReturnSelf();
    $quote->shouldReceive('getAttribute')->andReturnUsing(function ($key) use ($businessQuote) {
        return match ($key) {
            'first_name' => 'Corp',
            'last_name' => 'Client',
            'email' => 'corp@example.com',
            'mobile_no' => '+971501234567',
            'policy_number' => 'BUS-POL-002',
            'businessQuote' => $businessQuote,
            'payments' => collect(),
            default => null,
        };
    });
    $quote->shouldReceive('getRelationValue')->with('insuranceProvider')->andReturn(null);
    $quote->shouldReceive('getRelationValue')->with('currentlyInsuredWith')->andReturn(null);
    $quote->shouldReceive('getRelationValue')->with('advisor')->andReturn(null);
    $quote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($businessQuote);

    $failed = $this->service->mapFailedQuoteData($quote);

    expect($failed)->toHaveKey('product_type')
        ->and($failed['product_type'])->toBeNull();
});

it('sets product to Business insurance and quote_type to BUS in failed quote data', function () {
    $businessQuote = (object) ['businessTypeOfInsurance' => null, 'payments' => collect()];

    $quote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $quote->shouldReceive('loadMissing')->andReturnSelf();
    $quote->shouldReceive('getAttribute')->andReturnUsing(function ($key) use ($businessQuote) {
        return match ($key) {
            'first_name' => 'Corp',
            'email' => 'corp@example.com',
            'businessQuote' => $businessQuote,
            'payments' => collect(),
            default => null,
        };
    });
    $quote->shouldReceive('getRelationValue')->with('insuranceProvider')->andReturn(null);
    $quote->shouldReceive('getRelationValue')->with('currentlyInsuredWith')->andReturn(null);
    $quote->shouldReceive('getRelationValue')->with('advisor')->andReturn(null);
    $quote->shouldReceive('getRelationValue')->with('businessQuote')->andReturn($businessQuote);

    $failed = $this->service->mapFailedQuoteData($quote);

    expect($failed['product'])->toBe('Business insurance')
        ->and($failed['quote_type'])->toBe('BUS');
});

afterEach(function () {
    Mockery::close();
});
