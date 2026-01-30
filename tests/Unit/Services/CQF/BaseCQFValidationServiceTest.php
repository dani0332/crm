<?php

declare(strict_types=1);

use App\Services\CQF\BaseCQFValidationService;
use Illuminate\Database\Eloquent\Model;

beforeEach(function () {
    $this->service = new BaseCQFValidationService;
});

it('validates quote successfully when all base fields are present', function () {
    $quote = Mockery::mock(Model::class)->shouldIgnoreMissing();
    $quote->shouldReceive('toArray')->andReturn([
        'policy_number' => 'POL-001',
        'policy_expiry_date' => now()->addDays(30)->format('Y-m-d'),
        'first_name' => 'John',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
    ]);

    $result = $this->service->validateQuote($quote);

    expect($result)->toHaveKeys(['success', 'errors'])
        ->and($result['success'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

it('fails validation when policy_number is missing', function () {
    $quote = Mockery::mock(Model::class)->shouldIgnoreMissing();
    $quote->shouldReceive('toArray')->andReturn([
        'policy_expiry_date' => now()->addDays(30)->format('Y-m-d'),
        'first_name' => 'John',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
    ]);

    $result = $this->service->validateQuote($quote);

    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toHaveKey('policy_number');
});

it('fails validation when email is invalid', function () {
    $quote = Mockery::mock(Model::class)->shouldIgnoreMissing();
    $quote->shouldReceive('toArray')->andReturn([
        'policy_number' => 'POL-001',
        'policy_expiry_date' => now()->addDays(30)->format('Y-m-d'),
        'first_name' => 'John',
        'email' => 'not-an-email',
        'mobile_no' => '+971501234567',
    ]);

    $result = $this->service->validateQuote($quote);

    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toHaveKey('email');
});

it('returns validation messages', function () {
    $messages = $this->service->getValidationMessages();

    expect($messages)->toBeArray()
        ->and($messages)->toHaveKey('policy_number.required')
        ->and($messages)->toHaveKey('email.email');
});

it('returns false for isDuplicateQuote by default', function () {
    $quote = Mockery::mock(Model::class)->makePartial();

    expect($this->service->isDuplicateQuote($quote))->toBeFalse();
});

afterEach(function () {
    Mockery::close();
});
