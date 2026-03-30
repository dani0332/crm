<?php

declare(strict_types=1);

use App\Models\CustomerMembers;
use App\Models\Nationality;
use App\Traits\TransformsAuditables;
use Illuminate\Support\Facades\DB;

afterEach(function () {
    Mockery::close();
});

// ============================================================================
// Helpers
// ============================================================================

/**
 * Build a minimal data array for customizeAuditTransformation.
 *
 * @param  array<string, mixed>  $transformedOld
 * @param  array<string, mixed>  $transformedNew
 */
function auditData(string $event, array $transformedOld = [], array $transformedNew = [], ?object $model = null): array
{
    return [
        'audit' => (object) ['event' => $event, 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'],
        'transformedOld' => $transformedOld,
        'transformedNew' => $transformedNew,
        'model' => $model,
    ];
}

function makeModel(int $is_policy_holder = 0, int $is_principal = 0, string $first_name = 'John', string $last_name = 'Doe'): object
{
    return (object) compact('is_policy_holder', 'is_principal', 'first_name', 'last_name');
}

// ============================================================================
// Trait wiring
// ============================================================================

describe('TransformsAuditables trait', function () {
    test('is used by CustomerMembers model', function () {
        expect(in_array(TransformsAuditables::class, class_uses_recursive(CustomerMembers::class)))->toBeTrue();
    });

    test('transformAuditables returns array with required keys', function () {
        $data = auditData('created', [], ['is_policy_holder' => 'true']);

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result)->toHaveKeys(['audit', 'transformedOld', 'transformedNew']);
    });
});

// ============================================================================
// created event — all three branches
// ============================================================================

describe('CustomerMembers::customizeAuditTransformation – created event', function () {
    test('event is member_added when neither is_policy_holder nor is_principal is true', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('created', [], ['first_name' => 'John'])
        );

        expect($result['audit']->event)->toBe('member_added');
    });

    test('event is member_added (Policy Holder) when is_policy_holder equals "true"', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('created', [], ['is_policy_holder' => 'true'])
        );

        expect($result['audit']->event)->toBe('member_added (Policy Holder)');
    });

    test('event is member_added (Principal) when is_principal equals "true"', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('created', [], ['is_principal' => 'true'])
        );

        expect($result['audit']->event)->toBe('member_added (Principal)');
    });

    test('is_policy_holder takes precedence over is_principal on created event', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('created', [], ['is_policy_holder' => 'true', 'is_principal' => 'true'])
        );

        expect($result['audit']->event)->toBe('member_added (Policy Holder)');
    });

    test('is_policy_holder "false" string does not trigger policy holder branch', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('created', [], ['is_policy_holder' => 'false'])
        );

        expect($result['audit']->event)->toBe('member_added');
    });
});

// ============================================================================
// updated event — deletedAt branch
// ============================================================================

describe('CustomerMembers::customizeAuditTransformation – member_deleted', function () {
    test('event is member_deleted when deletedAt is present in transformedNew', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated', ['firstName' => 'John', 'lastName' => 'Doe'], ['deletedAt' => '2026-01-01 00:00:00'], makeModel())
        );

        expect($result['audit']->event)->toBe('member_deleted');
    });

    test('member_deleted takes priority over any other flag changes', function () {
        // Even if policy holder flags are also changing, deletedAt wins
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['firstName' => 'John', 'lastName' => 'Doe', 'isPolicyHolder' => 'true'],
                ['deletedAt' => '2026-01-01 00:00:00', 'isPolicyHolder' => 'false'],
                makeModel()
            )
        );

        expect($result['audit']->event)->toBe('member_deleted');
    });
});

// ============================================================================
// updated event — policy holder flag changes
// ============================================================================

describe('CustomerMembers::customizeAuditTransformation – policy holder flag changes', function () {
    test('event is member_updated (Policy Holder Removed) when isPolicyHolder changes true→false', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['firstName' => 'John', 'lastName' => 'Doe', 'isPolicyHolder' => 'true'],
                ['isPolicyHolder' => 'false'],
                makeModel()
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Policy Holder Removed)');
    });

    test('event is member_updated (Policy Holder Added) when isPolicyHolder changes false→true', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['firstName' => 'John', 'lastName' => 'Doe', 'isPolicyHolder' => 'false'],
                ['isPolicyHolder' => 'true'],
                makeModel(is_policy_holder: 1)
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Policy Holder Added)');
    });

    test('no policy holder event when only new isPolicyHolder key is present without old', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['firstName' => 'John', 'lastName' => 'Doe'],
                ['isPolicyHolder' => 'true'],
                makeModel()
            )
        );

        // Missing old key means condition is not met — falls through to member_updated
        expect($result['audit']->event)->toBe('member_updated');
    });
});

// ============================================================================
// updated event — principal flag changes
// ============================================================================

describe('CustomerMembers::customizeAuditTransformation – principal flag changes', function () {
    test('event is member_updated (Principal Removed) when isPrincipal changes true→false', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['firstName' => 'John', 'lastName' => 'Doe', 'isPrincipal' => 'true'],
                ['isPrincipal' => 'false'],
                makeModel()
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Principal Removed)');
    });

    test('event is member_updated (Principal Added) when isPrincipal changes false→true', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['firstName' => 'John', 'lastName' => 'Doe', 'isPrincipal' => 'false'],
                ['isPrincipal' => 'true'],
                makeModel(is_principal: 1)
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Principal Added)');
    });

    test('no principal event when only new isPrincipal key is present without old', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['firstName' => 'John', 'lastName' => 'Doe'],
                ['isPrincipal' => 'true'],
                makeModel()
            )
        );

        expect($result['audit']->event)->toBe('member_updated');
    });
});

// ============================================================================
// updated event — generic member_updated with model-based suffix
// ============================================================================

describe('CustomerMembers::customizeAuditTransformation – member_updated suffixes', function () {
    test('event is plain member_updated when model is neither policy holder nor principal', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['firstName' => 'John', 'lastName' => 'Doe'],
                ['firstName' => 'Jane'],
                makeModel(is_policy_holder: 0, is_principal: 0)
            )
        );

        expect($result['audit']->event)->toBe('member_updated');
    });

    test('event appends (Policy Holder) when model is_policy_holder is 1', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['firstName' => 'John', 'lastName' => 'Doe'],
                ['firstName' => 'Jane'],
                makeModel(is_policy_holder: 1, is_principal: 0)
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Policy Holder)');
    });

    test('event appends (Principal) when model is_principal is 1 and is_policy_holder is 0', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['firstName' => 'John', 'lastName' => 'Doe'],
                ['firstName' => 'Jane'],
                makeModel(is_policy_holder: 0, is_principal: 1)
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Principal)');
    });

    test('(Policy Holder) suffix takes precedence over (Principal) when both are 1', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['firstName' => 'John', 'lastName' => 'Doe'],
                ['firstName' => 'Jane'],
                makeModel(is_policy_holder: 1, is_principal: 1)
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Policy Holder)');
    });
});

// ============================================================================
// updated event — name population in transformedOld/New
// ============================================================================

describe('CustomerMembers::customizeAuditTransformation – name population', function () {
    test('populates name in transformedOld and transformedNew when firstName key is absent', function () {
        $model = makeModel(first_name: 'John', last_name: 'Doe');

        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated', ['gender' => 'M'], ['gender' => 'F'], $model)
        );

        expect($result['transformedOld']['name'])->toBe('John Doe')
            ->and($result['transformedNew']['name'])->toBe('John Doe');
    });

    test('does not overwrite name when firstName key is already present', function () {
        $model = makeModel(first_name: 'John', last_name: 'Doe');

        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['firstName' => 'OldFirst', 'lastName' => 'OldLast'],
                ['firstName' => 'NewFirst'],
                $model
            )
        );

        // name key should not be injected since firstName is present in transformedOld
        expect(isset($result['transformedOld']['name']))->toBeFalse();
    });

    test('name is null-safe when model is null (produces empty string)', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated', ['gender' => 'M'], ['gender' => 'F'], null)
        );

        // null?->first_name . ' ' . null?->last_name evaluates to ' ' (a space)
        expect($result['transformedOld']['name'])->toBe(' ')
            ->and($result['transformedNew']['name'])->toBe(' ');
    });
});

// ============================================================================
// Dataset-driven: all expected event strings for every branch
// ============================================================================

describe('CustomerMembers::customizeAuditTransformation – event string dataset', function () {
    test('produces correct event string for each scenario', function (string $expectedEvent, array $data) {
        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe($expectedEvent);
    })->with([
        'created – plain member' => [
            'member_added',
            auditData('created', [], []),
        ],
        'created – policy holder' => [
            'member_added (Policy Holder)',
            auditData('created', [], ['is_policy_holder' => 'true']),
        ],
        'created – principal' => [
            'member_added (Principal)',
            auditData('created', [], ['is_principal' => 'true']),
        ],
        'updated – deleted' => [
            'member_deleted',
            auditData('updated', ['firstName' => 'J', 'lastName' => 'D'], ['deletedAt' => '2026-01-01'], makeModel()),
        ],
        'updated – policy holder removed' => [
            'member_updated (Policy Holder Removed)',
            auditData('updated', ['firstName' => 'J', 'lastName' => 'D', 'isPolicyHolder' => 'true'], ['isPolicyHolder' => 'false'], makeModel()),
        ],
        'updated – policy holder added' => [
            'member_updated (Policy Holder Added)',
            auditData('updated', ['firstName' => 'J', 'lastName' => 'D', 'isPolicyHolder' => 'false'], ['isPolicyHolder' => 'true'], makeModel(is_policy_holder: 1)),
        ],
        'updated – principal removed' => [
            'member_updated (Principal Removed)',
            auditData('updated', ['firstName' => 'J', 'lastName' => 'D', 'isPrincipal' => 'true'], ['isPrincipal' => 'false'], makeModel()),
        ],
        'updated – principal added' => [
            'member_updated (Principal Added)',
            auditData('updated', ['firstName' => 'J', 'lastName' => 'D', 'isPrincipal' => 'false'], ['isPrincipal' => 'true'], makeModel(is_principal: 1)),
        ],
        'updated – plain' => [
            'member_updated',
            auditData('updated', ['firstName' => 'J', 'lastName' => 'D'], ['firstName' => 'K'], makeModel()),
        ],
        'updated – plain policy holder model' => [
            'member_updated (Policy Holder)',
            auditData('updated', ['firstName' => 'J', 'lastName' => 'D'], ['firstName' => 'K'], makeModel(is_policy_holder: 1)),
        ],
        'updated – plain principal model' => [
            'member_updated (Principal)',
            auditData('updated', ['firstName' => 'J', 'lastName' => 'D'], ['firstName' => 'K'], makeModel(is_principal: 1)),
        ],
    ]);
});

// ============================================================================
// performAuditTransformation — relational FK resolution
// ============================================================================

describe('performAuditTransformation – relational FK resolution', function () {
    test('old and new relational text are resolved from their respective FK ids, not from current model state', function () {
        // Seed two nationality rows with distinct names.
        $oldNationalityId = DB::table('nationality')->insertGetId(['text' => 'British', 'is_active' => 1]);
        $newNationalityId = DB::table('nationality')->insertGetId(['text' => 'Canadian', 'is_active' => 1]);

        // Create a CustomerMembers row whose current nationality_id points to the NEW value,
        // simulating what the DB looks like after the update has already been committed.
        $memberId = DB::table('customer_members')->insertGetId([
            'quote_type' => 'App\Models\HealthQuote',
            'quote_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'nationality_id' => $newNationalityId,
        ]);

        // Build the audit data the same way AuditRepository does: old_values has the previous FK,
        // new_values has the current FK.
        $data = [
            'audit' => (object) [
                'event' => 'updated',
                'auditable_id' => $memberId,
                'auditable_type' => CustomerMembers::class,
            ],
            'transformedOld' => ['nationality_id' => $oldNationalityId],
            'transformedNew' => ['nationality_id' => $newNationalityId],
        ];

        $result = (new CustomerMembers)->transformAuditables($data);

        // Old should resolve to "British" (id=$oldNationalityId), NOT "Canadian".
        expect($result['transformedOld']['nationality'])->toBe('British');
        // New should resolve to "Canadian" (id=$newNationalityId).
        expect($result['transformedNew']['nationality'])->toBe('Canadian');
    });

    test('old relational text is null when old FK id has no matching record', function () {
        $newNationalityId = DB::table('nationality')->insertGetId(['text' => 'German', 'is_active' => 1]);

        $memberId = DB::table('customer_members')->insertGetId([
            'quote_type' => 'App\Models\HealthQuote',
            'quote_id' => 1,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'nationality_id' => $newNationalityId,
        ]);

        $data = [
            'audit' => (object) [
                'event' => 'updated',
                'auditable_id' => $memberId,
                'auditable_type' => CustomerMembers::class,
            ],
            'transformedOld' => ['nationality_id' => 99999],
            'transformedNew' => ['nationality_id' => $newNationalityId],
        ];

        $result = (new CustomerMembers)->transformAuditables($data);

        expect($result['transformedOld']['nationality'])->toBeNull();
        expect($result['transformedNew']['nationality'])->toBe('German');
    });
});
