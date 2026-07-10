<?php

declare(strict_types=1);

use App\Services\CQF\NonMotor\LOBs\BusinessCQFValidationService;
use App\Services\CRUDService;
use Illuminate\Database\Eloquent\Model;

beforeEach(function () {
    $this->service = new BusinessCQFValidationService(Mockery::mock(CRUDService::class));
});

it('validates quote successfully when all business fields are present', function () {
    $quote = Mockery::mock(Model::class)->shouldIgnoreMissing();
    $quote->shouldReceive('toArray')->andReturn([
        'policy_number' => 'BUS-001',
        'policy_expiry_date' => now()->addDays(30)->format('Y-m-d'),
        'policy_start_date' => now()->subYear()->format('Y-m-d'),
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
        'business_type_of_insurance_id' => 1,
    ]);

    $result = $this->service->validateQuote($quote);

    expect($result['success'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

it('fails validation when business_type_of_insurance_id is missing', function () {
    $quote = Mockery::mock(Model::class)->shouldIgnoreMissing();
    $quote->shouldReceive('toArray')->andReturn([
        'policy_number' => 'BUS-001',
        'policy_expiry_date' => now()->addDays(30)->format('Y-m-d'),
        'policy_start_date' => now()->subYear()->format('Y-m-d'),
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
    ]);

    $result = $this->service->validateQuote($quote);

    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toHaveKey('business_type_of_insurance_id');
});

it('includes custom message for business_type_of_insurance_id.required', function () {
    $messages = $this->service->getValidationMessages();

    expect($messages)->toHaveKey('business_type_of_insurance_id.required')
        ->and($messages['business_type_of_insurance_id.required'])->toBe('Business type of insurance is required.');
});

it('inherits base validation messages', function () {
    $messages = $this->service->getValidationMessages();

    expect($messages)->toHaveKey('policy_number.required')
        ->and($messages)->toHaveKey('email.email');
});

it('fails validation on missing base fields even with business_type_of_insurance_id present', function () {
    $quote = Mockery::mock(Model::class)->shouldIgnoreMissing();
    $quote->shouldReceive('toArray')->andReturn([
        'business_type_of_insurance_id' => 1,
    ]);

    $result = $this->service->validateQuote($quote);

    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toHaveKey('policy_number')
        ->and($result['errors'])->toHaveKey('email');
});

afterEach(function () {
    Mockery::close();
});
