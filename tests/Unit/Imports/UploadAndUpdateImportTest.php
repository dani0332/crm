<?php

declare(strict_types=1);

use App\Imports\UploadAndUpdateImport;
use App\Models\RenewalsUploadLeads;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Facades\Validator;

/**
 * Regression tests for RenewalsImportTrait::isBlankImportCell via
 * UploadAndUpdateImport::mapQuoteData.
 *
 * Protects numeric fields — particularly `excess` at index 28 — from being
 * silently collapsed to null when a user enters 0 in the spreadsheet (e.g.
 * TPL plans where excess = 0 is required by uploadedLeadsValidation).
 */
function buildUploadAndUpdateRow(): array
{
    $row = array_fill(0, 46, '');
    $row[0] = 'Customer';
    $row[1] = 'test@example.com';
    $row[2] = '1234567890';
    $row[3] = 'CAR';
    $row[4] = 'RSA';
    $row[9] = 'Car Comprehensive';
    $row[10] = 'advisor@example.com';
    $row[11] = 'POL-001';
    $row[12] = '01/01/2026';
    $row[17] = '01/01/1990';

    return $row;
}

function newUploadAndUpdateImport(): UploadAndUpdateImport
{
    $service = (new ReflectionClass(RenewalsUploadService::class))->newInstanceWithoutConstructor();
    $lead = new RenewalsUploadLeads(['is_sic' => 0]);

    return new UploadAndUpdateImport($service, $lead);
}

test('mapQuoteData preserves excess 0 for TPL plans instead of collapsing to null', function () {
    $import = newUploadAndUpdateImport();
    $row = buildUploadAndUpdateRow();
    $row[22] = 'TPL';
    $row[28] = 0;

    $mapped = $import->mapQuoteData($row);

    expect($mapped['excess'])->toBe(0);
    expect($mapped['plan_type'])->toBe('TPL');
});

test('mapQuoteData preserves float 0.0 for excess and other numeric amount fields', function () {
    $import = newUploadAndUpdateImport();
    $row = buildUploadAndUpdateRow();
    $row[28] = 0.0;
    $row[29] = 0;
    $row[31] = 0;
    $row[33] = 0;
    $row[35] = 0;
    $row[37] = 0;
    $row[39] = 0;

    $mapped = $import->mapQuoteData($row);

    expect($mapped['excess'])->toBe(0.0);
    expect($mapped['ancillary_excess'])->toBe(0);
    expect($mapped['driver_cover_amount'])->toBe(0);
    expect($mapped['passenger_cover_amount'])->toBe(0);
    expect($mapped['car_hire_amount'])->toBe(0);
    expect($mapped['oman_cover_amount'])->toBe(0);
    expect($mapped['road_side_assistance_amount'])->toBe(0);
});

test('mapQuoteData still maps genuine empty cells to null for numeric fields', function () {
    $import = newUploadAndUpdateImport();
    $row = buildUploadAndUpdateRow();
    $row[28] = '';
    $row[29] = null;
    $row[27] = '';

    $mapped = $import->mapQuoteData($row);

    expect($mapped['excess'])->toBeNull();
    expect($mapped['ancillary_excess'])->toBeNull();
    expect($mapped['premium'])->toBeNull();
});

test('mapQuoteData treats date zero values as blank while preserving numeric zero fields', function () {
    $import = newUploadAndUpdateImport();
    $row = buildUploadAndUpdateRow();
    $row[12] = 0;
    $row[17] = '0';
    $row[28] = 0;

    $mapped = $import->mapQuoteData($row);

    expect($mapped['end_date'])->toBeNull();
    expect($mapped['dob'])->toBeNull();
    expect($mapped['excess'])->toBe(0);
});

test('validation allows zero in optional dob date field so row is not rejected before mapping', function () {
    $import = newUploadAndUpdateImport();
    $row = buildUploadAndUpdateRow();
    $row[17] = 0;

    $validator = Validator::make([$row], $import->getRules(), [], $import->customValidationAttributes());

    expect($validator->errors()->toArray())->not->toHaveKey('0.17');
});
