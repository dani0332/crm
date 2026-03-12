<?php

declare(strict_types=1);

use App\Imports\UploadAndCreateImport;
use App\Models\RenewalsUploadLeads;

test('getColumns does not include object column', function () {
    $lead = new RenewalsUploadLeads;
    $import = new UploadAndCreateImport($lead);
    $columns = $import->getColumns();

    expect($columns)->not->toHaveKey('object');
});

test('getColumns has correct indices for premium source notes and plan_name after object removal', function () {
    $lead = new RenewalsUploadLeads;
    $import = new UploadAndCreateImport($lead);
    $columns = $import->getColumns();

    expect($columns['premium']['index'])->toBe(16);
    expect($columns['source']['index'])->toBe(17);
    expect($columns['notes']['index'])->toBe(18);
    expect($columns['plan_name']['index'])->toBe(19);
});

test('getColumns has exactly 20 columns for upload and create template', function () {
    $lead = new RenewalsUploadLeads;
    $import = new UploadAndCreateImport($lead);
    $columns = $import->getColumns();

    expect($columns)->toHaveCount(20);
});

test('mapQuoteData does not throw when row has fewer columns than schema', function () {
    $lead = new RenewalsUploadLeads;
    $import = new UploadAndCreateImport($lead);

    // Row with only 19 columns (indices 0-18); plan_name at index 19 is missing
    $row = array_fill(0, 19, '');
    $row[0] = 'Customer';
    $row[1] = 'test@example.com';
    $row[2] = '1234567890';
    $row[3] = 'CAR';
    $row[8] = 'POL-001';
    $row[10] = '01/01/2025';

    $mapped = $import->mapQuoteData($row);

    expect($mapped)->toHaveKey('plan_name');
    expect($mapped['plan_name'])->toBeNull();
    expect($mapped['customer_name'])->toBe('Customer');
});
