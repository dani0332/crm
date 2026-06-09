<?php

use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Exports\RenewalFailedValidationExport;
use App\Imports\UploadAndCreateImport;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
});

it('builds CREATE_LEADS export header from UploadAndCreateImport columns and adds errors column', function () {
    $uploadLead = RenewalsUploadLeads::factory()->create([
        'quote_type' => 'LIF',
        'renewal_import_code' => 'test-'.Str::uuid(),
        'renewal_import_type' => RenewalsUploadType::CREATE_LEADS,
    ]);

    $export = new RenewalFailedValidationExport($uploadLead);
    $collection = $export->collection();

    $headerRow = $collection->first();
    $columns = (new UploadAndCreateImport($uploadLead))->getColumns();
    foreach (array_keys($columns) as $key) {
        expect($headerRow)->toHaveProperty($key);
    }
    expect($headerRow)->toHaveProperty('errors');
    expect($headerRow->errors)->toBe('Error Message(s)');
    expect($headerRow->premium)->toBe('Gross Premium');
});

it('maps failed lead data with previous_quote_policy_premium to premium for createQuote re-upload format', function () {
    $uploadLead = RenewalsUploadLeads::factory()->create([
        'quote_type' => 'LIF',
        'renewal_import_code' => 'test-'.Str::uuid(),
        'renewal_import_type' => RenewalsUploadType::CREATE_LEADS,
    ]);

    RenewalQuoteProcess::factory()->create([
        'renewals_upload_lead_id' => $uploadLead->id,
        'quote_id' => 0,
        'quote_type' => 'LIF',
        'policy_number' => 'POL-001',
        'batch' => null,
        'status' => RenewalProcessStatuses::VALIDATION_FAILED,
        'type' => RenewalsUploadType::CREATE_LEADS,
        'data' => [
            'customer_name' => 'John Doe',
            'email' => 'john@example.com',
            'mobile_no' => '1234567890',
            'quote_type' => 'LIF',
            'insurer' => 'Provider A',
            'product' => 'Life',
            'policy_number' => 'POL-001',
            'end_date' => '31/12/2025',
            'previous_quote_policy_premium' => 150.50,
        ],
        'validation_errors' => ['Some error'],
    ]);

    $export = new RenewalFailedValidationExport($uploadLead);
    $collection = $export->collection();

    $dataRow = $collection->get(1);
    expect($dataRow->premium)->toBe(150.50);
    expect($dataRow->customer_name)->toBe('John Doe');
    expect($dataRow->errors)->toBe('Some error');
});

it('uses premium when present and ignores previous_quote_policy_premium for export row', function () {
    $uploadLead = RenewalsUploadLeads::factory()->create([
        'quote_type' => 'BIK',
        'renewal_import_code' => 'test-'.Str::uuid(),
        'renewal_import_type' => RenewalsUploadType::CREATE_LEADS,
    ]);

    RenewalQuoteProcess::factory()->create([
        'renewals_upload_lead_id' => $uploadLead->id,
        'quote_id' => 0,
        'quote_type' => 'BIK',
        'policy_number' => 'POL-002',
        'batch' => null,
        'status' => RenewalProcessStatuses::BAD_DATA,
        'type' => RenewalsUploadType::CREATE_LEADS,
        'data' => [
            'customer_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'mobile_no' => '0987654321',
            'quote_type' => 'BIK',
            'premium' => 99.99,
            'previous_quote_policy_premium' => 150.50,
        ],
        'validation_errors' => null,
    ]);

    $export = new RenewalFailedValidationExport($uploadLead);
    $collection = $export->collection();

    $dataRow = $collection->get(1);
    expect($dataRow->premium)->toBe(99.99);
});
