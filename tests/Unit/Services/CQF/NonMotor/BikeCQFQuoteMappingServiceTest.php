<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Services\CQF\NonMotor\LOBs\BikeCQFQuoteMappingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->service = (new ReflectionClass(BikeCQFQuoteMappingService::class))->newInstanceWithoutConstructor();
});

it('returns empty array when quote is not PersonalQuote', function () {
    $quote = Mockery::mock(Model::class);
    $uploadLead = Mockery::mock(RenewalsUploadLeads::class)->shouldIgnoreMissing();
    $uploadLead->shouldReceive('getAttribute')->with('renewal_import_code')->andReturn('IMP-001');

    $result = $this->service->mapRenewalQuote($quote, $uploadLead, Str::uuid()->toString());

    expect($result)->toBeArray()->toBeEmpty();
});

it('maps personal quote to renewal data with required fields', function () {
    $quote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $quote->shouldReceive('loadMissing')->with('payments')->andReturnSelf();
    $quote->shouldReceive('getAttribute')->andReturnUsing(function ($key) {
        $data = [
            'customer_id' => 1,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'mobile_no' => '+971509876543',
            'dob' => '1990-01-15',
            'advisor_id' => null,
            'policy_number' => 'BIK-POL-001',
            'policy_start_date' => now()->subDays(365),
            'policy_expiry_date' => now()->addDays(30),
            'premium' => 500.00,
            'nationality_id' => 1,
            'currently_insured_with_id' => 1,
            'insurance_provider_id' => 1,
            'payments' => collect(),
        ];

        return $data[$key] ?? null;
    });

    $uploadLead = Mockery::mock(RenewalsUploadLeads::class)->shouldIgnoreMissing();
    $uploadLead->shouldReceive('getAttribute')->with('renewal_import_code')->andReturn('IMP-001');

    $uuid = Str::uuid()->toString();
    $mapped = $this->service->mapRenewalQuote($quote, $uploadLead, $uuid);

    expect($mapped)->toBeArray()
        ->and($mapped)->toHaveKeys(['first_name', 'last_name', 'email', 'mobile_no', 'uuid', 'source', 'quote_status_id', 'renewal_import_code', 'previous_quote_policy_number', 'quote_type_id'])
        ->and($mapped['first_name'])->toBe('Jane')
        ->and($mapped['email'])->toBe('jane@example.com')
        ->and($mapped['uuid'])->toBe($uuid)
        ->and($mapped['source'])->toBe(LeadSourceEnum::RENEWAL_UPLOAD)
        ->and($mapped['quote_status_id'])->toBe(QuoteStatusEnum::NewLead);
});

it('maps failed quote data with customer and policy info', function () {
    $quote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $quote->shouldReceive('loadMissing')->with('payments')->andReturnSelf();
    $quote->shouldReceive('getAttribute')->andReturnUsing(function ($key) {
        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'mobile_no' => '+971509876543',
            'policy_number' => 'BIK-POL-001',
            'policy_start_date' => now()->subDays(365),
            'policy_expiry_date' => now()->addDays(30),
            'premium' => 500.00,
            'source' => 'WEB',
            'notes' => null,
            'payments' => collect(),
        ];

        return $data[$key] ?? null;
    });
    $quote->shouldReceive('getRelationValue')->with('insuranceProvider')->andReturn(null);
    $quote->shouldReceive('getRelationValue')->with('currentlyInsuredWith')->andReturn(null);
    $quote->shouldReceive('getRelationValue')->with('advisor')->andReturn(null);
    $quote->shouldReceive('getRelationValue')->with('bikeQuote')->andReturn(null);

    $failed = $this->service->mapFailedQuoteData($quote);

    expect($failed)->toBeArray()
        ->and($failed)->toHaveKeys(['customer_name', 'email', 'mobile_no', 'quote_type', 'policy_number', 'product'])
        ->and($failed['customer_name'])->toContain('Jane')
        ->and($failed['quote_type'])->toBe('BIK')
        ->and($failed['product'])->toBe('Bike insurance');
});

afterEach(function () {
    Mockery::close();
});
