<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Services\SageFailedRecordsService;
use Carbon\Carbon;

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
