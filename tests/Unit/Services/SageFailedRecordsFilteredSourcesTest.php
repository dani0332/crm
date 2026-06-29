<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
use App\Services\SageFailedRecordsService;
use Carbon\Carbon;

function sageFailedRecordsShouldFilterByLeadCreatedDate(array $requestPayload): bool
{
    $method = new ReflectionMethod(SageFailedRecordsService::class, 'shouldFilterByLeadCreatedDate');

    return $method->invoke(sageFailedRecordsService(), $requestPayload);
}

function sageFailedRecordsShouldFilterBySageApiFailureDate(array $requestPayload): bool
{
    $method = new ReflectionMethod(SageFailedRecordsService::class, 'shouldFilterBySageApiFailureDate');

    return $method->invoke(sageFailedRecordsService(), $requestPayload);
}

function sageFailedRecordsService(): SageFailedRecordsService
{
    return app(SageFailedRecordsService::class);
}

function sageFailedRecordsPdo(): PDO
{
    return new PDO('sqlite:memory');
}

function sageFailedRecordsFilteredSources(array $requestPayload): array
{
    $method = new ReflectionMethod(SageFailedRecordsService::class, 'getFilteredSources');

    return $method->invoke(sageFailedRecordsService(), sageFailedRecordsPdo(), $requestPayload);
}

function sageFailedRecordsFailedLeadsUnionSql(array $requestPayload): string
{
    $filteredSources = sageFailedRecordsFilteredSources($requestPayload);
    $method = new ReflectionMethod(SageFailedRecordsService::class, 'getFailedLeadsUnionSubquery');

    return $method->invoke(
        sageFailedRecordsService(),
        sageFailedRecordsPdo(),
        $requestPayload,
        $filteredSources
    );
}

test('failed leads union subquery stays valid when send update option conflicts with car quote type', function () {
    $sql = sageFailedRecordsFailedLeadsUnionSql([
        'option' => 'Send Update',
        'quote_type_id' => ['1'],
        'date_from' => '2024-01-01',
        'date_to' => '2024-01-31',
    ]);

    expect($sql)->toContain('WHERE 1 = 0');
});

test('failed leads union subquery quotes date bounds with PDO for SQL safety', function () {
    $pdo = sageFailedRecordsPdo();
    $dateFrom = '2024-01-15';
    $dateTo = '2024-01-20';

    $sql = sageFailedRecordsFailedLeadsUnionSql([
        'option' => 'Main Lead',
        'quote_type_id' => [],
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ]);

    $dbDateFormat = config('constants.DB_DATE_FORMAT_MATCH');
    $startQuoted = $pdo->quote(Carbon::parse($dateFrom)->startOfDay()->format($dbDateFormat));
    $endQuoted = $pdo->quote(Carbon::parse($dateTo)->endOfDay()->format($dbDateFormat));

    expect($sql)->toContain("AND created_at BETWEEN {$startQuoted} AND {$endQuoted}");
});

test('get filtered sources narrows personal quotes to requested personal quote type ids', function () {
    $sources = sageFailedRecordsFilteredSources([
        'option' => 'Main Lead',
        'quote_type_id' => [(string) QuoteTypeId::Car, (string) QuoteTypeId::Bike, (string) QuoteTypeId::Home],
    ]);

    $personal = collect($sources)->first(fn (array $s): bool => ($s['model'] ?? '') === PersonalQuote::class);

    expect($personal['quote_type_ids'])
        ->toBe([QuoteTypeId::Bike, QuoteTypeId::Home]);
});

test('failed leads union subquery restricts personal_quotes by intersected quote_type_id list', function () {
    $sql = sageFailedRecordsFailedLeadsUnionSql([
        'option' => 'Main Lead',
        'quote_type_id' => [(string) QuoteTypeId::Home],
        'date_from' => '2024-01-01',
        'date_to' => '2024-01-31',
    ]);

    expect($sql)->toContain('FROM personal_quotes')
        ->and($sql)->toContain('AND quote_type_id IN ('.QuoteTypeId::Home.')');
});

// region Lead Status Filter

test('lead status filter defaults to IN when no filter is provided', function () {
    $sql = sageFailedRecordsFailedLeadsUnionSql([
        'option' => 'Main Lead',
        'date_from' => '2024-01-01',
        'date_to' => '2024-01-31',
    ]);

    expect($sql)->toContain(' IN (')
        ->and($sql)->not->toContain('NOT IN');
});

test('lead status filter Policy Booking Failed generates IN status condition', function () {
    $sql = sageFailedRecordsFailedLeadsUnionSql([
        'option' => 'Main Lead',
        'lead_status_filter' => SageFailedRecordsService::LEAD_STATUS_FILTER_POLICY_BOOKING_FAILED,
        'date_from' => '2024-01-01',
        'date_to' => '2024-01-31',
    ]);

    expect($sql)->toContain(' IN (')
        ->and($sql)->not->toContain('NOT IN');
});

test('lead status filter Other Status generates NOT IN status condition', function () {
    $sql = sageFailedRecordsFailedLeadsUnionSql([
        'option' => 'Main Lead',
        'lead_status_filter' => SageFailedRecordsService::LEAD_STATUS_FILTER_OTHER_STATUS,
        'date_from' => '2024-01-01',
        'date_to' => '2024-01-31',
    ]);

    expect($sql)->toContain('NOT IN');
});

// endregion

// region Date Filter Type

test('shouldFilterByLeadCreatedDate returns true when date_filter_type is empty', function () {
    expect(sageFailedRecordsShouldFilterByLeadCreatedDate(['date_filter_type' => []]))->toBeTrue();
});

test('shouldFilterByLeadCreatedDate returns true when date_filter_type is not set', function () {
    expect(sageFailedRecordsShouldFilterByLeadCreatedDate([]))->toBeTrue();
});

test('shouldFilterByLeadCreatedDate returns true when Lead Created Date is selected', function () {
    expect(sageFailedRecordsShouldFilterByLeadCreatedDate([
        'date_filter_type' => [SageFailedRecordsService::DATE_FILTER_TYPE_LEAD_CREATED],
    ]))->toBeTrue();
});

test('shouldFilterByLeadCreatedDate returns false when only Sage API Failure Date is selected', function () {
    expect(sageFailedRecordsShouldFilterByLeadCreatedDate([
        'date_filter_type' => [SageFailedRecordsService::DATE_FILTER_TYPE_SAGE_API_FAILURE],
    ]))->toBeFalse();
});

test('shouldFilterBySageApiFailureDate returns false when date_filter_type is empty', function () {
    expect(sageFailedRecordsShouldFilterBySageApiFailureDate(['date_filter_type' => []]))->toBeFalse();
});

test('shouldFilterBySageApiFailureDate returns true when Sage API Failure Date is selected', function () {
    expect(sageFailedRecordsShouldFilterBySageApiFailureDate([
        'date_filter_type' => [SageFailedRecordsService::DATE_FILTER_TYPE_SAGE_API_FAILURE],
    ]))->toBeTrue();
});

test('shouldFilterBySageApiFailureDate returns true when both date types are selected', function () {
    expect(sageFailedRecordsShouldFilterBySageApiFailureDate([
        'date_filter_type' => [
            SageFailedRecordsService::DATE_FILTER_TYPE_LEAD_CREATED,
            SageFailedRecordsService::DATE_FILTER_TYPE_SAGE_API_FAILURE,
        ],
    ]))->toBeTrue();
});

test('date filter type Lead Created Date includes lead created_at condition in subquery', function () {
    $pdo = sageFailedRecordsPdo();
    $dateFrom = '2024-02-01';
    $dateTo = '2024-02-28';
    $dbDateFormat = config('constants.DB_DATE_FORMAT_MATCH');
    $startQuoted = $pdo->quote(Carbon::parse($dateFrom)->startOfDay()->format($dbDateFormat));
    $endQuoted = $pdo->quote(Carbon::parse($dateTo)->endOfDay()->format($dbDateFormat));

    $sql = sageFailedRecordsFailedLeadsUnionSql([
        'option' => 'Main Lead',
        'date_filter_type' => [SageFailedRecordsService::DATE_FILTER_TYPE_LEAD_CREATED],
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ]);

    expect($sql)->toContain("created_at BETWEEN {$startQuoted} AND {$endQuoted}");
});

test('date filter type Sage API Failure Date only omits lead created_at condition from subquery', function () {
    $sql = sageFailedRecordsFailedLeadsUnionSql([
        'option' => 'Main Lead',
        'date_filter_type' => [SageFailedRecordsService::DATE_FILTER_TYPE_SAGE_API_FAILURE],
        'date_from' => '2024-02-01',
        'date_to' => '2024-02-28',
    ]);

    expect($sql)->not->toContain('created_at BETWEEN');
});

test('empty date filter type defaults to lead created_at filter in subquery', function () {
    $sql = sageFailedRecordsFailedLeadsUnionSql([
        'option' => 'Main Lead',
        'date_filter_type' => [],
        'date_from' => '2024-02-01',
        'date_to' => '2024-02-28',
    ]);

    expect($sql)->toContain('created_at BETWEEN');
});

test('both date filter types selected includes lead created_at condition in subquery', function () {
    $sql = sageFailedRecordsFailedLeadsUnionSql([
        'option' => 'Main Lead',
        'date_filter_type' => [
            SageFailedRecordsService::DATE_FILTER_TYPE_LEAD_CREATED,
            SageFailedRecordsService::DATE_FILTER_TYPE_SAGE_API_FAILURE,
        ],
        'date_from' => '2024-02-01',
        'date_to' => '2024-02-28',
    ]);

    expect($sql)->toContain('created_at BETWEEN');
});

// endregion

test('morph model classes for eager load are derived from filtered sources without querying oldest_logs CTE', function () {
    $filteredSources = sageFailedRecordsFilteredSources([
        'option' => 'Main Lead',
        'quote_type_id' => [(string) QuoteTypeId::Car],
    ]);

    $method = new ReflectionMethod(SageFailedRecordsService::class, 'morphModelClassesFromFailedLeadSources');
    $morphClasses = $method->invoke(sageFailedRecordsService(), $filteredSources);

    expect($morphClasses)->toContain(CarQuote::class)
        ->and($morphClasses)->not->toContain(SendUpdateLog::class);
});
