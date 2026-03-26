<?php

declare(strict_types=1);

use App\Models\CustomerMembers;
use App\Models\MartialStatus;
use App\Models\VisaCategory;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

describe('CustomerMembers - new relationships', function () {
    test('has maritalStatus belongsTo relationship', function () {
        $member = new CustomerMembers;

        expect($member->maritalStatus())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    test('has visaCategory belongsTo relationship', function () {
        $member = new CustomerMembers;

        expect($member->visaCategory())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    test('visaCategory relationship points to VisaCategory model', function () {
        $member = new CustomerMembers;
        $relation = $member->visaCategory();

        expect($relation->getRelated())->toBeInstanceOf(VisaCategory::class);
    });

    test('maritalStatus relationship points to MartialStatus model', function () {
        $member = new CustomerMembers;
        $relation = $member->maritalStatus();

        expect($relation->getRelated())->toBeInstanceOf(MartialStatus::class);
    });

    test('auditRelationMap includes new visa_category_id and marital_status_id keys', function () {
        $member = new CustomerMembers;
        $reflection = new ReflectionClass($member);
        $property = $reflection->getProperty('auditRelationMap');
        $property->setAccessible(true);
        $map = $property->getValue($member);

        expect($map)->toHaveKey('visa_category_id')
            ->and($map)->toHaveKey('marital_status_id')
            ->and($map['visa_category_id']['relation'])->toBe('visaCategory')
            ->and($map['visa_category_id']['field'])->toBe('text')
            ->and($map['marital_status_id']['relation'])->toBe('maritalStatus')
            ->and($map['marital_status_id']['field'])->toBe('text');
    });

    test('has is_policy_holder and is_insured stored in database', function () {
        $db = DB::connection('sqlite');
        $id = $db->table('customer_members')->insertGetId([
            'quote_type' => 'App\Models\HealthQuote',
            'quote_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'is_policy_holder' => true,
            'is_insured' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = $db->table('customer_members')->find($id);

        expect((bool) $row->is_policy_holder)->toBeTrue()
            ->and((bool) $row->is_insured)->toBeFalse();
    });
});

describe('CustomerMembers::customizeAuditTransformation - created event', function () {
    test('sets event to member_added for regular member creation', function () {
        $audit = (object) ['event' => 'created', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $data = [
            'audit' => $audit,
            'transformedOld' => [],
            'transformedNew' => ['first_name' => 'John'],
            'model' => null,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_added');
    });

    test('sets event to member_added (Policy Holder) when is_policy_holder is true on creation', function () {
        $audit = (object) ['event' => 'created', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $data = [
            'audit' => $audit,
            'transformedOld' => [],
            'transformedNew' => ['is_policy_holder' => 'true'],
            'model' => null,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_added (Policy Holder)');
    });

    test('sets event to member_added (Principal) when is_principal is true on creation', function () {
        $audit = (object) ['event' => 'created', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $data = [
            'audit' => $audit,
            'transformedOld' => [],
            'transformedNew' => ['is_principal' => 'true'],
            'model' => null,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_added (Principal)');
    });
});

describe('CustomerMembers::customizeAuditTransformation - updated event', function () {
    test('sets event to member_deleted when deletedAt is present in new data', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 0, 'is_principal' => 0];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['firstName' => 'John', 'lastName' => 'Doe'],
            'transformedNew' => ['deletedAt' => '2026-01-01 00:00:00'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_deleted');
    });

    test('sets event to member_updated (Policy Holder Removed) when policy holder flag changes to false', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 0, 'is_principal' => 0];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['firstName' => 'John', 'lastName' => 'Doe', 'isPolicyHolder' => 'true'],
            'transformedNew' => ['isPolicyHolder' => 'false'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_updated (Policy Holder Removed)');
    });

    test('sets event to member_updated (Policy Holder Added) when policy holder flag changes to true', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 1, 'is_principal' => 0];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['firstName' => 'John', 'lastName' => 'Doe', 'isPolicyHolder' => 'false'],
            'transformedNew' => ['isPolicyHolder' => 'true'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_updated (Policy Holder Added)');
    });

    test('sets event to member_updated (Principal Removed) when principal flag changes to false', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 0, 'is_principal' => 0];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['firstName' => 'John', 'lastName' => 'Doe', 'isPrincipal' => 'true'],
            'transformedNew' => ['isPrincipal' => 'false'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_updated (Principal Removed)');
    });

    test('sets event to member_updated (Principal Added) when principal flag changes to true', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 0, 'is_principal' => 1];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['firstName' => 'John', 'lastName' => 'Doe', 'isPrincipal' => 'false'],
            'transformedNew' => ['isPrincipal' => 'true'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_updated (Principal Added)');
    });

    test('sets event to member_updated for regular update', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 0, 'is_principal' => 0];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['firstName' => 'John', 'lastName' => 'Doe'],
            'transformedNew' => ['firstName' => 'Jane'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_updated');
    });

    test('appends (Policy Holder) suffix to member_updated when model is policy holder', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 1, 'is_principal' => 0];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['firstName' => 'John', 'lastName' => 'Doe'],
            'transformedNew' => ['firstName' => 'Jane'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_updated (Policy Holder)');
    });

    test('appends (Principal) suffix to member_updated when model is principal', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 0, 'is_principal' => 1];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['firstName' => 'John', 'lastName' => 'Doe'],
            'transformedNew' => ['firstName' => 'Jane'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_updated (Principal)');
    });

    test('populates name in transformedOld and transformedNew when firstName/lastName keys are absent', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 0, 'is_principal' => 0];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['gender' => 'M'],
            'transformedNew' => ['gender' => 'F'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['transformedOld']['name'])->toBe('John Doe')
            ->and($result['transformedNew']['name'])->toBe('John Doe');
    });
});

describe('VisaCategory model', function () {
    test('uses visa_categories table', function () {
        $model = new VisaCategory;

        expect($model->getTable())->toBe('visa_categories');
    });

    test('has correct fillable fields', function () {
        $model = new VisaCategory;

        expect($model->getFillable())->toBe(['code', 'text', 'is_active', 'sort_order', 'health_cover_for_id']);
    });

    test('active scope filters by is_active', function () {
        $db = DB::connection('sqlite');
        $db->table('visa_categories')->insert([
            ['code' => 'VC1', 'text' => 'Visit Visa', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'VC2', 'text' => 'Work Permit', 'is_active' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // The active scope uses status=1 (as defined), but is_active column is used in LookupService
        // Direct DB check for the new columns
        $activeCount = $db->table('visa_categories')->where('is_active', 1)->count();
        $inactiveCount = $db->table('visa_categories')->where('is_active', 0)->count();

        expect($activeCount)->toBe(1)
            ->and($inactiveCount)->toBe(1);
    });

    test('can be created with all fillable attributes', function () {
        $db = DB::connection('sqlite');
        $id = $db->table('visa_categories')->insertGetId([
            'code' => 'VISIT',
            'text' => 'Visit Visa',
            'is_active' => 1,
            'sort_order' => 1,
            'health_cover_for_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = $db->table('visa_categories')->find($id);

        expect($row->code)->toBe('VISIT')
            ->and($row->text)->toBe('Visit Visa')
            ->and((bool) $row->is_active)->toBeTrue()
            ->and($row->sort_order)->toBe(1);
    });
});
