<?php

use App\Enums\GenericRequestEnum;
use App\Services\Reports\Reportable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    config(['constants.DB_DATE_FORMAT_MATCH' => 'Y-m-d H:i:s']);

    // Insert the MAX_DAYS value into application_storage
    DB::table('application_storage')->insert([
        'key_name' => GenericRequestEnum::MAX_DAYS,
        'value' => 30,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

// Create a test class that uses the Reportable trait
function createReportableInstance()
{
    return new class
    {
        use Reportable;
    };
}

it('returns correct dates when both start and end dates are provided', function () {
    Carbon::setTestNow('2026-01-28 12:00:00');

    $reportable = createReportableInstance();

    $filters = (object) [
        'advisorAssignedDates' => [
            '2026-01-01T00:00:00.000Z',
            '2026-01-15T23:59:59.000Z',
        ],
    ];

    [$freshLoad, $startDate, $endDate] = $reportable->getStartAndEndDate($filters);

    expect($freshLoad)->toBeTrue()
        ->and($startDate)->toBe('2026-01-01 00:00:00')
        ->and($endDate)->toBe('2026-01-15 23:59:59');
});

it('uses first date as end date when only one date is provided', function () {
    Carbon::setTestNow('2026-01-28 12:00:00');

    $reportable = createReportableInstance();

    $filters = (object) [
        'advisorAssignedDates' => [
            '2026-01-28T18:29:00.000Z',
        ],
    ];

    [$freshLoad, $startDate, $endDate] = $reportable->getStartAndEndDate($filters);

    expect($freshLoad)->toBeTrue()
        ->and($startDate)->toBe('2026-01-28 00:00:00')
        ->and($endDate)->toBe('2026-01-28 23:59:59');
});

it('uses today as start and end date on fresh load when no dates provided', function () {
    Carbon::setTestNow('2026-01-28 12:00:00');

    $reportable = createReportableInstance();

    $filters = (object) [];

    [$freshLoad, $startDate, $endDate] = $reportable->getStartAndEndDate($filters);

    expect($freshLoad)->toBeTrue()
        ->and($startDate)->toBe('2026-01-28 00:00:00')
        ->and($endDate)->toBe('2026-01-28 23:59:59');
});

it('uses max days ago as start date on paginated load when no dates provided', function () {
    Carbon::setTestNow('2026-01-28 12:00:00');

    $reportable = createReportableInstance();

    $filters = (object) [
        'page' => 2,
    ];

    [$freshLoad, $startDate, $endDate] = $reportable->getStartAndEndDate($filters);

    expect($freshLoad)->toBeFalse()
        ->and($startDate)->toBe('2025-12-29 00:00:00') // 30 days before Jan 28
        ->and($endDate)->toBe('2026-01-28 23:59:59');
});

it('handles custom date attribute name', function () {
    Carbon::setTestNow('2026-01-28 12:00:00');

    $reportable = createReportableInstance();

    $filters = (object) [
        'customDates' => [
            '2026-01-10T00:00:00.000Z',
            '2026-01-20T23:59:59.000Z',
        ],
    ];

    [$freshLoad, $startDate, $endDate] = $reportable->getStartAndEndDate($filters, 'customDates');

    expect($freshLoad)->toBeTrue()
        ->and($startDate)->toBe('2026-01-10 00:00:00')
        ->and($endDate)->toBe('2026-01-20 23:59:59');
});

it('handles single date with custom date attribute name', function () {
    Carbon::setTestNow('2026-01-28 12:00:00');

    $reportable = createReportableInstance();

    $filters = (object) [
        'createdAtFilter' => [
            '2026-01-15T10:30:00.000Z',
        ],
    ];

    [$freshLoad, $startDate, $endDate] = $reportable->getStartAndEndDate($filters, 'createdAtFilter');

    expect($freshLoad)->toBeTrue()
        ->and($startDate)->toBe('2026-01-15 00:00:00')
        ->and($endDate)->toBe(now()->endOfDay()->format('Y-m-d H:i:s'));
});
