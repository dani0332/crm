<?php

declare(strict_types=1);

use App\Models\CustomerMembers;
use App\Models\Nationality;
use App\Traits\AuditTransformLookupCache;
use App\Traits\TransformsAuditables;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
    AuditTransformLookupCache::flush();
});

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

describe('AuditTransformLookupCache', function () {
    test('flush clears both lookup stores', function () {
        $ref = new ReflectionClass(AuditTransformLookupCache::class);
        $auditable = $ref->getProperty('auditableByKey');
        $auditable->setAccessible(true);
        $related = $ref->getProperty('relatedByKey');
        $related->setAccessible(true);

        $auditable->setValue(null, ['probe' => null]);
        $related->setValue(null, ['probe' => null]);

        AuditTransformLookupCache::flush();

        expect($auditable->getValue())->toBe([]);
        expect($related->getValue())->toBe([]);
    });

    test('flushAuditTransformLookupCaches on model delegates to unified cache', function () {
        $ref = new ReflectionClass(AuditTransformLookupCache::class);
        $auditable = $ref->getProperty('auditableByKey');
        $auditable->setAccessible(true);
        $auditable->setValue(null, ['delegated' => null]);

        CustomerMembers::flushAuditTransformLookupCaches();

        expect($auditable->getValue())->toBe([]);
    });
});

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

    test('event is member_added (Policyholder) when is_policy_holder and is_insured equal "true"', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('created', [], ['is_policy_holder' => 'true', 'is_insured' => 'true'])
        );

        expect($result['audit']->event)->toBe('member_added (Policyholder)');
    });

    test('event is member_added (Non-insured Policyholder) when is_policy_holder is "true" but is_insured is absent from audit new values', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('created', [], ['is_policy_holder' => 'true'])
        );

        expect($result['audit']->event)->toBe('member_added (Non-insured Policyholder)');
    });

    test('event is member_added (Principal) when is_principal equals "true"', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('created', [], ['is_principal' => 'true'])
        );

        expect($result['audit']->event)->toBe('member_added (Principal)');
    });

    test('is_policy_holder takes precedence over is_principal on created event', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('created', [], ['is_policy_holder' => 'true', 'is_principal' => 'true', 'is_insured' => 'true'])
        );

        expect($result['audit']->event)->toBe('member_added (Policyholder)');
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
    test('event is member_deleted when deleted_at is present in transformedNew', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated', ['first_name' => 'John', 'last_name' => 'Doe'], ['deleted_at' => '2026-01-01 00:00:00'], makeModel())
        );

        expect($result['audit']->event)->toBe('member_deleted');
    });

    test('member_deleted takes priority over any other flag changes', function () {
        // Even if policy holder flags are also changing, deleted_at wins
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 'true'],
                ['deleted_at' => '2026-01-01 00:00:00', 'is_policy_holder' => 'false'],
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
    test('event is member_updated (Policyholder Removed) when is_policy_holder changes true→false', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 'true'],
                ['is_policy_holder' => 'false'],
                makeModel()
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Policyholder Removed)');
    });

    test('event is member_updated (Policyholder Added) when is_policy_holder changes false→true', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 'false'],
                ['is_policy_holder' => 'true'],
                makeModel(is_policy_holder: 1)
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Policyholder Added)');
    });

    test('no policy holder event when only new is_policy_holder key is present without old', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['first_name' => 'John', 'last_name' => 'Doe'],
                ['is_policy_holder' => 'true'],
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
    test('event is member_updated (Principal Removed) when is_principal changes true→false', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['first_name' => 'John', 'last_name' => 'Doe', 'is_principal' => 'true'],
                ['is_principal' => 'false'],
                makeModel()
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Principal Removed)');
    });

    test('event is member_updated (Principal Added) when is_principal changes false→true', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['first_name' => 'John', 'last_name' => 'Doe', 'is_principal' => 'false'],
                ['is_principal' => 'true'],
                makeModel(is_principal: 1)
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Principal Added)');
    });

    test('no principal event when only new is_principal key is present without old', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['first_name' => 'John', 'last_name' => 'Doe'],
                ['is_principal' => 'true'],
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
                ['first_name' => 'John', 'last_name' => 'Doe'],
                ['first_name' => 'Jane'],
                makeModel(is_policy_holder: 0, is_principal: 0)
            )
        );

        expect($result['audit']->event)->toBe('member_updated');
    });

    test('event appends (Policyholder) when model is_policy_holder is 1', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['first_name' => 'John', 'last_name' => 'Doe'],
                ['first_name' => 'Jane'],
                makeModel(is_policy_holder: 1, is_principal: 0)
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Policyholder)');
    });

    test('event appends (Principal) when model is_principal is 1 and is_policy_holder is 0', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['first_name' => 'John', 'last_name' => 'Doe'],
                ['first_name' => 'Jane'],
                makeModel(is_policy_holder: 0, is_principal: 1)
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Principal)');
    });

    test('(Policyholder) suffix takes precedence over (Principal) when both are 1', function () {
        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['first_name' => 'John', 'last_name' => 'Doe'],
                ['first_name' => 'Jane'],
                makeModel(is_policy_holder: 1, is_principal: 1)
            )
        );

        expect($result['audit']->event)->toBe('member_updated (Policyholder)');
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

    test('does not overwrite name when first_name key is already present', function () {
        $model = makeModel(first_name: 'John', last_name: 'Doe');

        $result = CustomerMembers::customizeAuditTransformation(
            auditData('updated',
                ['first_name' => 'OldFirst', 'last_name' => 'OldLast'],
                ['first_name' => 'NewFirst'],
                $model
            )
        );

        // name key should not be injected since first_name is present in transformedOld
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
        'created – policy holder (insured)' => [
            'member_added (Policyholder)',
            auditData('created', [], ['is_policy_holder' => 'true', 'is_insured' => 'true']),
        ],
        'created – policy holder (is_insured absent from audit)' => [
            'member_added (Non-insured Policyholder)',
            auditData('created', [], ['is_policy_holder' => 'true']),
        ],
        'created – principal' => [
            'member_added (Principal)',
            auditData('created', [], ['is_principal' => 'true']),
        ],
        'updated – deleted' => [
            'member_deleted',
            auditData('updated', ['first_name' => 'J', 'last_name' => 'D'], ['deleted_at' => '2026-01-01'], makeModel()),
        ],
        'updated – policy holder removed' => [
            'member_updated (Policyholder Removed)',
            auditData('updated', ['first_name' => 'J', 'last_name' => 'D', 'is_policy_holder' => 'true'], ['is_policy_holder' => 'false'], makeModel()),
        ],
        'updated – policy holder added' => [
            'member_updated (Policyholder Added)',
            auditData('updated', ['first_name' => 'J', 'last_name' => 'D', 'is_policy_holder' => 'false'], ['is_policy_holder' => 'true'], makeModel(is_policy_holder: 1)),
        ],
        'updated – principal removed' => [
            'member_updated (Principal Removed)',
            auditData('updated', ['first_name' => 'J', 'last_name' => 'D', 'is_principal' => 'true'], ['is_principal' => 'false'], makeModel()),
        ],
        'updated – principal added' => [
            'member_updated (Principal Added)',
            auditData('updated', ['first_name' => 'J', 'last_name' => 'D', 'is_principal' => 'false'], ['is_principal' => 'true'], makeModel(is_principal: 1)),
        ],
        'updated – plain' => [
            'member_updated',
            auditData('updated', ['first_name' => 'J', 'last_name' => 'D'], ['first_name' => 'K'], makeModel()),
        ],
        'updated – plain policy holder model' => [
            'member_updated (Policyholder)',
            auditData('updated', ['first_name' => 'J', 'last_name' => 'D'], ['first_name' => 'K'], makeModel(is_policy_holder: 1)),
        ],
        'updated – plain principal model' => [
            'member_updated (Principal)',
            auditData('updated', ['first_name' => 'J', 'last_name' => 'D'], ['first_name' => 'K'], makeModel(is_principal: 1)),
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

        expect($result['transformedOld'])->not->toHaveKey('nationality');
        expect($result['transformedNew']['nationality'])->toBe('German');
    });
});
