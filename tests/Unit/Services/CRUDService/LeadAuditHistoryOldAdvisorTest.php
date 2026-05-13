<?php

use App\Services\CRUDService;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

/**
 * The OldAdvisor SQL below must match {@see CRUDService::getLeadAuditHistory()}.
 */
function leadHistoryOldAdvisorExpressionForDriver(): string
{
    $driver = DB::getDriverName();

    if (in_array($driver, ['mysql', 'mariadb'], true)) {
        return 'NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.old_values, \'$.advisor_id\')), \'null\'), IF(JSON_EXTRACT(a.new_values, \'$.advisor_id\') IS NULL, NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.old_values, \'$.advisor_id\')), \'null\'), NULL))';
    }

    return "NULLIF(
        NULLIF(json_extract(a.old_values, '$.advisor_id'), 'null'),
        CASE
            WHEN json_extract(a.new_values, '$.advisor_id') IS NULL
            THEN NULLIF(json_extract(a.old_values, '$.advisor_id'), 'null')
            ELSE NULL
        END
    )";
}

beforeEach(function () {
    config(['database.default' => 'sqlite']);
    DB::setDefaultConnection('sqlite');
    TestSchemaCreator::createMinimalSchema();
});

it('returns null OldAdvisor when the audit is status-only but old_values still contains advisor_id', function () {
    $oldAdvisorId = DB::table('users')->insertGetId([
        'name' => 'Old Advisor',
        'email' => 'old-'.uniqid().'@example.com',
        'password' => 'secret',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $auditId = DB::table('audits')->insertGetId([
        'user_id' => null,
        'event' => 'updated',
        'auditable_type' => 'App\\Models\\TravelQuote',
        'auditable_id' => 1,
        'old_values' => json_encode(['advisor_id' => (string) $oldAdvisorId]),
        'new_values' => json_encode(['quote_status_id' => 1]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $expr = leadHistoryOldAdvisorExpressionForDriver();
    $oldName = DB::table('audits as a')
        ->selectRaw('(SELECT name FROM users WHERE id = '.$expr.') as OldAdvisor')
        ->where('a.id', $auditId)
        ->value('OldAdvisor');

    expect($oldName)->toBeNull();
});

it('resolves OldAdvisor when new_values includes advisor_id and old_values had a different advisor', function () {
    $oldAdvisorId = DB::table('users')->insertGetId([
        'name' => 'Previous Advisor',
        'email' => 'prev-'.uniqid().'@example.com',
        'password' => 'secret',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $newAdvisorId = DB::table('users')->insertGetId([
        'name' => 'New Assignee',
        'email' => 'new-'.uniqid().'@example.com',
        'password' => 'secret',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $auditId = DB::table('audits')->insertGetId([
        'user_id' => null,
        'event' => 'updated',
        'auditable_type' => 'App\\Models\\TravelQuote',
        'auditable_id' => 1,
        'old_values' => json_encode(['advisor_id' => (string) $oldAdvisorId]),
        'new_values' => json_encode(['advisor_id' => (string) $newAdvisorId]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $expr = leadHistoryOldAdvisorExpressionForDriver();
    $oldName = DB::table('audits as a')
        ->selectRaw('(SELECT name FROM users WHERE id = '.$expr.') as OldAdvisor')
        ->where('a.id', $auditId)
        ->value('OldAdvisor');

    expect($oldName)->toBe('Previous Advisor');
});
