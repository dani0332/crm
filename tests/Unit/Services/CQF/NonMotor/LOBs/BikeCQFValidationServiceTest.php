<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Services\CQF\NonMotor\LOBs\BikeCQFValidationService;
use Illuminate\Support\Facades\Config;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\SchemaUtils;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    Config::set('constants.DATE_FORMAT', 'Y-m-d');

    SchemaUtils::addColumnIfMissing('car_quote_request', 'policy_number', function ($table) {
        $table->string('policy_number')->nullable();
    });
    SchemaUtils::addColumnIfMissing('personal_quotes', 'previous_quote_id', function ($table) {
        $table->unsignedBigInteger('previous_quote_id')->nullable();
    });
    SchemaUtils::addColumnIfMissing('personal_quotes', 'previous_quote_policy_number', function ($table) {
        $table->string('previous_quote_policy_number')->nullable();
    });
    SchemaUtils::addColumnIfMissing('personal_quotes', 'previous_policy_expiry_date', function ($table) {
        $table->date('previous_policy_expiry_date')->nullable();
    });
    SchemaUtils::addColumnIfMissing('personal_quotes', 'source', function ($table) {
        $table->string('source')->nullable();
    });

    $this->service = app(BikeCQFValidationService::class);
});

it('returns false when no PersonalQuote exists with the CarQuote uuid', function () {
    $quote = CarQuote::factory()->create();

    expect($this->service->isDuplicateQuote($quote))->toBeFalse();
});

it('returns false when PersonalQuote exists for uuid but no bike renewal duplicate exists', function () {
    $quote = CarQuote::factory()->create([
        'policy_number' => 'CAR-POL-001',
        'policy_expiry_date' => now()->addDays(30)->format('Y-m-d'),
    ]);

    PersonalQuote::factory()->createForSqlite([
        'uuid' => $quote->uuid,
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    expect($this->service->isDuplicateQuote($quote))->toBeFalse();
});

it('returns true when a bike renewal already exists with matching previous_quote_id', function () {
    $policyNumber = 'CAR-POL-002';
    $expiryDate = now()->addDays(30)->format('Y-m-d');

    $quote = CarQuote::factory()->create([
        'policy_number' => $policyNumber,
        'policy_expiry_date' => $expiryDate,
    ]);

    $previousPersonalQuote = PersonalQuote::factory()->createForSqlite([
        'uuid' => $quote->uuid,
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    PersonalQuote::factory()->createForSqlite([
        'quote_type_id' => QuoteTypeId::Bike,
        'previous_quote_id' => $previousPersonalQuote->id,
        'previous_quote_policy_number' => $policyNumber,
        'previous_policy_expiry_date' => $expiryDate,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
    ]);

    expect($this->service->isDuplicateQuote($quote))->toBeTrue();
});

it('returns false when CarQuote uuid does not match any PersonalQuote uuid', function () {
    $policyNumber = 'CAR-POL-003';
    $expiryDate = now()->addDays(30)->format('Y-m-d');

    $previousPersonalQuote = PersonalQuote::factory()->createForSqlite([
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    PersonalQuote::factory()->createForSqlite([
        'quote_type_id' => QuoteTypeId::Bike,
        'previous_quote_id' => $previousPersonalQuote->id,
        'previous_quote_policy_number' => $policyNumber,
        'previous_policy_expiry_date' => $expiryDate,
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
    ]);

    // CarQuote uuid is distinct from $previousPersonalQuote->uuid — lookup by uuid finds nothing,
    // so no duplicate match even though policy_number and expiry_date align.
    $quote = CarQuote::factory()->create([
        'policy_number' => $policyNumber,
        'policy_expiry_date' => $expiryDate,
    ]);

    expect($this->service->isDuplicateQuote($quote))->toBeFalse();
});
