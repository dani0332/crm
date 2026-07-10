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

test('getColumns has correct indices for premium previous fields then source notes plan_name', function () {
    $lead = new RenewalsUploadLeads;
    $import = new UploadAndCreateImport($lead);
    $columns = $import->getColumns();

    expect($columns['premium']['index'])->toBe(16);
    expect($columns['previous_commission']['index'])->toBe(17);
    expect($columns['previous_ref_id']['index'])->toBe(18);
    expect($columns['source']['index'])->toBe(19);
    expect($columns['notes']['index'])->toBe(20);
    expect($columns['plan_name']['index'])->toBe(21);
});

test('getColumns has exactly 22 columns for upload and create template', function () {
    $lead = new RenewalsUploadLeads;
    $import = new UploadAndCreateImport($lead);
    $columns = $import->getColumns();

    expect($columns)->toHaveCount(22);
});

test('mapQuoteData does not throw when row has fewer columns than schema', function () {
    $lead = new RenewalsUploadLeads;
    $import = new UploadAndCreateImport($lead);

    // Row with only 19 columns (indices 0-18); previous_ref_id (18) through plan_name (21) missing
    $row = array_fill(0, 19, '');
    $row[0] = 'Customer';
    $row[1] = 'test@example.com';
    $row[2] = '1234567890';
    $row[3] = 'CAR';
    $row[8] = 'POL-001';
    $row[10] = '01/01/2025';

    $mapped = $import->mapQuoteData($row);

    expect($mapped)->toHaveKey('plan_name')
        ->and($mapped['plan_name'])->toBeNull()
        ->and($mapped['previous_ref_id'])->toBeNull()
        ->and($mapped['source'])->toBeNull()
        ->and($mapped['customer_name'])->toBe('Customer');
});

test('mapQuoteData preserves numeric zero premium so user-entered 0 is not silently dropped', function () {
    // Regression: isBlankImportCell previously treated int/float 0 as blank and
    // mapped it to null. That caused real data loss for numeric fields where 0
    // is a legitimate user-entered value (e.g. UploadAndUpdateImport::excess
    // for TPL plans, amount fields). Only null/'' should be treated as blank.
    $lead = new RenewalsUploadLeads;
    $import = new UploadAndCreateImport($lead);

    $row = array_fill(0, 22, '');
    $row[0] = 'Customer';
    $row[1] = 'test@example.com';
    $row[2] = '1234567890';
    $row[3] = 'CAR';
    $row[8] = 'POL-001';
    $row[10] = '01/01/2025';
    $row[16] = 0;

    $mappedInt = $import->mapQuoteData($row);
    expect($mappedInt['premium'])->toBe(0);

    $row[16] = 0.0;
    $mappedFloat = $import->mapQuoteData($row);
    expect($mappedFloat['premium'])->toBe(0.0);

    $row[16] = '0';
    $mappedStringZero = $import->mapQuoteData($row);
    expect($mappedStringZero['premium'])->toBe('0');

    $row[16] = null;
    $mappedNull = $import->mapQuoteData($row);
    expect($mappedNull['premium'])->toBeNull();

    $row[16] = '';
    $mappedEmpty = $import->mapQuoteData($row);
    expect($mappedEmpty['premium'])->toBeNull();
});

test('mapQuoteData maps previous_commission previous_ref_id source notes and plan_name at trailing indices', function () {
    $lead = new RenewalsUploadLeads;
    $import = new UploadAndCreateImport($lead);

    $row = array_fill(0, 22, '');
    $row[0] = 'Customer';
    $row[1] = 'test@example.com';
    $row[2] = '1234567890';
    $row[3] = 'CAR';
    $row[8] = 'POL-001';
    $row[10] = '01/01/2025';
    $row[17] = 12.5;
    $row[18] = 'PET-OLD-001';
    $row[19] = 'renewal_upload';
    $row[20] = 'Note text';
    $row[21] = 'Gold';

    $mapped = $import->mapQuoteData($row);

    expect($mapped['previous_commission'])->toBe(12.5)
        ->and($mapped['previous_ref_id'])->toBe('PET-OLD-001')
        ->and($mapped['source'])->toBe('renewal_upload')
        ->and($mapped['notes'])->toBe('Note text')
        ->and($mapped['plan_name'])->toBe('Gold');
});
