<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\InsuranceProvider;
use App\Models\PersonalQuote;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    if (! extension_loaded('pdo_sqlite')) {
        test()->markTestSkipped('PDO SQLite driver is required for this test.');
    }

    config(['database.default' => 'sqlite']);
    DB::setDefaultConnection('sqlite');

    $sqliteConfig = config('database.connections.sqlite');
    config([
        'database.connections.mysql.driver' => 'sqlite',
        'database.connections.mysql.database' => $sqliteConfig['database'] ?? ':memory:',
        'database.connections.mysql.prefix' => $sqliteConfig['prefix'] ?? '',
    ]);
    DB::purge('mysql');
    DB::reconnect('mysql');

    TestSchemaCreator::createRenewalsSchema();
});

test('currently_insured_with_text is null when business_quote_request has no personal_quote_id', function () {
    $businessQuote = BusinessQuote::factory()->create(['personal_quote_id' => null]);

    $result = DB::table('business_quote_request as bqr')
        ->select('ciw.text as currently_insured_with_text')
        ->leftJoin('personal_quotes as pq_ciw', 'pq_ciw.id', '=', 'bqr.personal_quote_id')
        ->leftJoin('insurance_provider as ciw', 'ciw.id', '=', 'pq_ciw.currently_insured_with_id')
        ->where('bqr.uuid', $businessQuote->uuid)
        ->first();

    expect($result->currently_insured_with_text)->toBeNull();
});

test('currently_insured_with_text is returned when business renewal sets personal_quote_id after sync', function () {
    $provider = InsuranceProvider::factory()->create(['text' => 'Test Insurer Co.']);

    $businessQuote = BusinessQuote::factory()->create(['personal_quote_id' => null]);

    $personalQuote = PersonalQuote::factory()->createForSqlite([
        'uuid' => $businessQuote->uuid,
        'code' => 'BUS-'.$businessQuote->uuid,
        'quote_type_id' => QuoteTypeId::Business,
        'currently_insured_with_id' => $provider->id,
    ]);

    // Simulate the fix: renewal upload service now sets personal_quote_id after updatePersonalQuote
    $businessQuote->personal_quote_id = $personalQuote->id;
    $businessQuote->saveQuietly();

    $result = DB::table('business_quote_request as bqr')
        ->select('ciw.text as currently_insured_with_text')
        ->leftJoin('personal_quotes as pq_ciw', 'pq_ciw.id', '=', 'bqr.personal_quote_id')
        ->leftJoin('insurance_provider as ciw', 'ciw.id', '=', 'pq_ciw.currently_insured_with_id')
        ->where('bqr.uuid', $businessQuote->uuid)
        ->first();

    expect($result->currently_insured_with_text)->toBe('Test Insurer Co.');
});
