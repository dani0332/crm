<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Services\SageFailedRecordsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

test('failed leads union subquery stays valid when send update option conflicts with car quote type', function () {
    $service = app(SageFailedRecordsService::class);
    $pdo = DB::connection()->getPdo();

    $method = new ReflectionMethod(SageFailedRecordsService::class, 'getFailedLeadsUnionSubquery');
    $method->setAccessible(true);

    $sql = $method->invoke($service, $pdo, [
        'option' => 'Send Update',
        'quote_type_id' => ['1'],
        'date_from' => '2024-01-01',
        'date_to' => '2024-01-31',
    ]);

    expect($sql)->not->toBe('')
        ->and($sql)->toContain('WHERE 1 = 0');
});

test('failed leads union subquery stays valid when main lead option conflicts with send update quote type', function () {
    $service = app(SageFailedRecordsService::class);
    $pdo = DB::connection()->getPdo();

    $method = new ReflectionMethod(SageFailedRecordsService::class, 'getFailedLeadsUnionSubquery');
    $method->setAccessible(true);

    $sql = $method->invoke($service, $pdo, [
        'option' => 'Main Lead',
        'quote_type_id' => ['Send Update'],
        'date_from' => '2024-01-01',
        'date_to' => '2024-01-31',
    ]);

    expect($sql)->toContain('WHERE 1 = 0');
});

test('get filtered sources returns all main lead sources when quote_type_id is empty', function () {
    $service = app(SageFailedRecordsService::class);
    $pdo = DB::connection()->getPdo();

    $method = new ReflectionMethod(SageFailedRecordsService::class, 'getFilteredSources');
    $method->setAccessible(true);

    $sources = $method->invoke($service, $pdo, [
        'option' => 'Main Lead',
        'quote_type_id' => [],
    ]);

    expect($sources)->not->toBeEmpty()
        ->and(count($sources))->toBe(6);
});

test('failed leads union subquery quotes date bounds with PDO for SQL safety', function () {
    $service = app(SageFailedRecordsService::class);
    $pdo = DB::connection()->getPdo();

    $method = new ReflectionMethod(SageFailedRecordsService::class, 'getFailedLeadsUnionSubquery');
    $method->setAccessible(true);

    $dateFrom = '2024-01-15';
    $dateTo = '2024-01-20';

    $sql = $method->invoke($service, $pdo, [
        'option' => 'Main Lead',
        'quote_type_id' => [],
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
    ]);

    $startQuoted = $pdo->quote(Carbon::parse($dateFrom)->startOfDay()->format('Y-m-d H:i:s'));
    $endQuoted = $pdo->quote(Carbon::parse($dateTo)->endOfDay()->format('Y-m-d H:i:s'));

    expect($sql)->toContain("AND created_at BETWEEN {$startQuoted} AND {$endQuoted}");
});

test('get filtered sources narrows personal quotes to requested personal quote type ids', function () {
    $service = app(SageFailedRecordsService::class);
    $pdo = DB::connection()->getPdo();

    $method = new ReflectionMethod(SageFailedRecordsService::class, 'getFilteredSources');
    $method->setAccessible(true);

    $sources = $method->invoke($service, $pdo, [
        'option' => 'Main Lead',
        'quote_type_id' => [(string) QuoteTypeId::Car, (string) QuoteTypeId::Bike, (string) QuoteTypeId::Home],
    ]);

    $personal = collect($sources)->first(fn (array $s): bool => ($s['model'] ?? '') === PersonalQuote::class);

    expect($personal)->not->toBeNull()
        ->and($personal['quote_type_ids'])->toBe([QuoteTypeId::Bike, QuoteTypeId::Home]);
});

test('failed leads union subquery restricts personal_quotes by intersected quote_type_id list', function () {
    $service = app(SageFailedRecordsService::class);
    $pdo = DB::connection()->getPdo();

    $method = new ReflectionMethod(SageFailedRecordsService::class, 'getFailedLeadsUnionSubquery');
    $method->setAccessible(true);

    $sql = $method->invoke($service, $pdo, [
        'option' => 'Main Lead',
        'quote_type_id' => [(string) QuoteTypeId::Home],
        'date_from' => '2024-01-01',
        'date_to' => '2024-01-31',
    ]);

    expect($sql)->toContain('FROM personal_quotes')
        ->and($sql)->toContain('AND quote_type_id IN ('.QuoteTypeId::Home.')');
});
