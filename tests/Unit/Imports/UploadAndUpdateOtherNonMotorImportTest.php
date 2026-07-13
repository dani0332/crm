<?php

declare(strict_types=1);

use App\Enums\FetchPlansStatuses;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Imports\UploadAndUpdateOtherNonMotorImport;
use App\Models\RenewalsUploadLeads;
use App\Services\OtherNonMotorRenewalsUploadService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
});

function makeOtherNonMotorImport(): UploadAndUpdateOtherNonMotorImport
{
    $service = (new ReflectionClass(OtherNonMotorRenewalsUploadService::class))->newInstanceWithoutConstructor();
    $lead = RenewalsUploadLeads::factory()->forQuoteType(OtherNonMotorRenewalsUploadService::QUOTE_TYPE)->create();

    return new UploadAndUpdateOtherNonMotorImport($service, $lead);
}

function seedResolvedPolicyNumbers(UploadAndUpdateOtherNonMotorImport $import, array $map): void
{
    $prop = new ReflectionProperty($import, 'resolvedPolicyNumbers');
    $prop->setValue($import, $map);
}

test('model sets policy_number from resolved cache when quote has a previous policy number', function () {
    $import = makeOtherNonMotorImport();
    seedResolvedPolicyNumbers($import, ['OTH-ABC123' => 'POL-2024-001']);

    $row = array_fill(0, 2, '');
    $row[0] = 'OTH-ABC123';
    $row[1] = 'advisor@example.com';

    $process = $import->model($row);

    expect($process->policy_number)->toBe('POL-2024-001');
});

test('model sets policy_number to null when quote has no previous policy number', function () {
    $import = makeOtherNonMotorImport();
    seedResolvedPolicyNumbers($import, ['OTH-ABC123' => null]);

    $row = array_fill(0, 2, '');
    $row[0] = 'OTH-ABC123';
    $row[1] = 'advisor@example.com';

    $process = $import->model($row);

    expect($process->policy_number)->toBeNull();
});

test('model sets policy_number to null when ref_id is not in resolved cache', function () {
    $import = makeOtherNonMotorImport();

    $row = array_fill(0, 2, '');
    $row[0] = 'OTH-MISSING';
    $row[1] = 'advisor@example.com';

    $process = $import->model($row);

    expect($process->policy_number)->toBeNull();
});

test('model sets correct status and quote_type fields', function () {
    $import = makeOtherNonMotorImport();

    $row = array_fill(0, 2, '');
    $row[0] = 'OTH-ABC123';
    $row[1] = 'advisor@example.com';

    $process = $import->model($row);

    expect($process->status)->toBe(RenewalProcessStatuses::NEW)
        ->and($process->quote_type)->toBe(OtherNonMotorRenewalsUploadService::QUOTE_TYPE)
        ->and($process->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING)
        ->and($process->type)->toBe(RenewalsUploadType::UPDATE_LEADS);
});
