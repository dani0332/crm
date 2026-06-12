<?php

declare(strict_types=1);

use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\Customer;
use App\Models\Lookup;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Services\CQF\NonMotor\CQFRenewalContext;
use App\Services\CQF\NonMotor\LOBs\PetCQFQuoteMappingService;
use App\Services\CQF\NonMotor\LOBs\PetCQFQuoteStorageService;
use App\Services\CQF\NonMotor\LOBs\PetCQFValidationService;
use App\Services\CQF\NonMotor\Pipes\ForeignKeyValidationPipe;
use Illuminate\Database\Eloquent\Model;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
    $this->pipe = app(ForeignKeyValidationPipe::class);
});

it('passes through when quote is not PersonalQuote', function () {
    $quote = Mockery::mock(Model::class)->shouldIgnoreMissing();
    $renewalsUploadLeads = RenewalsUploadLeads::factory()->create([
        'quote_type' => 'Bike',
        'renewal_import_code' => 'test-'.uniqid(),
        'file_name' => 'test.xlsx',
        'status' => '1',
    ]);
    $context = new CQFRenewalContext(
        quote: $quote,
        renewalsUploadLeads: $renewalsUploadLeads,
        quoteType: QuoteTypes::BIKE,
        renewalDaysThreshold: 120,
        validator: null,
        mapper: null,
        storage: null
    );

    $result = $this->pipe->handle($context, fn ($c) => $c);

    expect($result)->toBe($context)
        ->and($result->hasErrors())->toBeFalse();
});

it('passes when PersonalQuote has all required FKs existing', function () {
    $customer = Customer::factory()->create();
    $nationality = Nationality::factory()->create();
    Lookup::forceCreate([
        'key' => LookupsEnum::TRANSACTION_TYPES->value,
        'code' => LookupsEnum::EXT_CUSTOMER_RENWAL->value,
        'text' => 'Ext Customer Renewal',
    ]);
    $quote = PersonalQuote::factory()->create([
        'quote_type_id' => QuoteTypeId::Pet,
        'customer_id' => $customer->id,
        'nationality_id' => $nationality->id,
        'insurance_provider_id' => null,
        'policy_expiry_date' => now()->addMonths(2),
    ]);
    $renewalsUploadLeads = RenewalsUploadLeads::factory()->create([
        'quote_type' => 'Pet',
        'renewal_import_code' => 'test-'.uniqid(),
        'file_name' => 'test.xlsx',
        'status' => '1',
    ]);
    $context = new CQFRenewalContext(
        quote: $quote,
        renewalsUploadLeads: $renewalsUploadLeads,
        quoteType: QuoteTypes::PET,
        renewalDaysThreshold: 120,
        validator: app(PetCQFValidationService::class),
        mapper: app(PetCQFQuoteMappingService::class),
        storage: app(PetCQFQuoteStorageService::class)
    );

    $result = $this->pipe->handle($context, fn ($c) => $c);

    expect($result->hasErrors())->toBeFalse();
});

afterEach(function () {
    Mockery::close();
});
