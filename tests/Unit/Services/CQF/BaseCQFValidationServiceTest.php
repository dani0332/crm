<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Services\CQF\BaseCQFValidationService;
use App\Services\CRUDService;
use Illuminate\Database\Eloquent\Model;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\SchemaUtils;

beforeEach(function () {
    $this->service = new BaseCQFValidationService(Mockery::mock(CRUDService::class));
});

it('validates quote successfully when all base fields are present', function () {
    $quote = Mockery::mock(Model::class)->shouldIgnoreMissing();
    $quote->shouldReceive('toArray')->andReturn([
        'policy_number' => 'POL-001',
        'policy_expiry_date' => now()->addDays(30)->format('Y-m-d'),
        'policy_start_date' => now()->subYear()->format('Y-m-d'),
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
    ]);

    $result = $this->service->validateQuote($quote);

    expect($result)->toHaveKeys(['success', 'errors'])
        ->and($result['success'])->toBeTrue()
        ->and($result['errors'])->toBeEmpty();
});

it('fails validation when policy_start_date is missing', function () {
    $quote = Mockery::mock(Model::class)->shouldIgnoreMissing();
    $quote->shouldReceive('toArray')->andReturn([
        'policy_number' => 'POL-001',
        'policy_expiry_date' => now()->addDays(30)->format('Y-m-d'),
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
    ]);

    $result = $this->service->validateQuote($quote);

    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toHaveKey('policy_start_date');
});

it('fails validation when last_name is missing', function () {
    $quote = Mockery::mock(Model::class)->shouldIgnoreMissing();
    $quote->shouldReceive('toArray')->andReturn([
        'policy_number' => 'POL-001',
        'policy_expiry_date' => now()->addDays(30)->format('Y-m-d'),
        'policy_start_date' => now()->subYear()->format('Y-m-d'),
        'first_name' => 'John',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
    ]);

    $result = $this->service->validateQuote($quote);

    expect($result['success'])->toBeFalse()
        ->and($result['errors'])->toHaveKey('last_name');
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

describe('isDuplicateQuote with database', function () {
    beforeEach(function () {
        TestSchemaCreator::createMinimalSchema();
        config(['constants.DATE_FORMAT' => 'd/m/Y']);

        SchemaUtils::addColumnIfMissing('personal_quotes', 'previous_quote_id', fn ($t) => $t->unsignedBigInteger('previous_quote_id')->nullable());
        SchemaUtils::addColumnIfMissing('personal_quotes', 'previous_quote_policy_number', fn ($t) => $t->string('previous_quote_policy_number')->nullable());
        SchemaUtils::addColumnIfMissing('personal_quotes', 'previous_policy_expiry_date', fn ($t) => $t->date('previous_policy_expiry_date')->nullable());
    });

    it('returns true when a renewal copy already exists for the quote', function () {
        $expiryDate = now()->addYear()->toDateString();

        $original = PersonalQuote::factory()->create([
            'quote_type_id' => QuoteTypeId::Cycle,
            'policy_expiry_date' => $expiryDate,
        ]);

        PersonalQuote::factory()->create([
            'quote_type_id' => QuoteTypeId::Cycle,
            'previous_quote_id' => $original->id,
            'previous_quote_policy_number' => $original->policy_number,
            'previous_policy_expiry_date' => $expiryDate,
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
        ]);

        expect($this->service->isDuplicateQuote($original))->toBeTrue();
    });
});

afterEach(function () {
    Mockery::close();
});
