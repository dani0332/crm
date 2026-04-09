<?php

declare(strict_types=1);

use App\Models\PersonalQuote;
use App\Models\RenewalsUploadLeads;
use App\Services\CQF\NonMotor\LOBs\YachtCQFQuoteMappingService;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->service = app(YachtCQFQuoteMappingService::class);
});

it('maps previous_quote_policy_premium from price_with_vat instead of premium', function () {
    $quote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $quote->shouldReceive('getAttribute')->andReturnUsing(function ($key) {
        $data = [
            'customer_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john@example.com',
            'mobile_no' => '+971501234567',
            'dob' => '1985-05-10',
            'advisor_id' => 5,
            'policy_number' => 'YCH-POL-001',
            'policy_start_date' => now()->subDays(365),
            'policy_expiry_date' => now()->addDays(30),
            'premium' => 2000.00,
            'price_with_vat' => 2100.00,
            'nationality_id' => 1,
            'currently_insured_with_id' => 1,
            'insurance_provider_id' => 1,
        ];

        return $data[$key] ?? null;
    });

    $uploadLead = Mockery::mock(RenewalsUploadLeads::class)->shouldIgnoreMissing();
    $uploadLead->shouldReceive('getAttribute')->with('renewal_import_code')->andReturn('IMP-002');

    $uuid = Str::uuid()->toString();
    $mapped = $this->service->mapRenewalQuote($quote, $uploadLead, $uuid);

    expect($mapped)->toBeArray()
        ->and($mapped['previous_quote_policy_premium'])->toBe(2100.00)
        ->and($mapped['previous_advisor_id'])->toBe(5);
});

it('maps previous_quote_policy_premium as price_with_vat in failed export data', function () {
    $quote = new PersonalQuote;
    $quote->forceFill([
        'first_name' => 'John',
        'last_name' => 'Smith',
        'email' => 'john@example.com',
        'mobile_no' => '+971501234567',
        'policy_number' => 'YCH-POL-001',
        'policy_start_date' => now()->subDays(365)->toDateString(),
        'policy_expiry_date' => now()->addDays(30)->toDateString(),
        'premium' => 2000.00,
        'price_with_vat' => 2100.00,
        'source' => 'WEB',
    ]);

    $failed = $this->service->mapFailedQuoteData($quote);

    expect($failed)->toBeArray()
        ->and($failed['previous_quote_policy_premium'])->toBe(2100.00);
});

afterEach(function () {
    Mockery::close();
});
