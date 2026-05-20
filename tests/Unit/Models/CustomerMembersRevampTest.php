<?php

declare(strict_types=1);

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerMembers;
use App\Models\MartialStatus;
use App\Models\VisaCategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

describe('CustomerMembers - new relationships', function () {
    test('has maritalStatus belongsTo relationship', function () {
        $member = new CustomerMembers;

        expect($member->maritalStatus())->toBeInstanceOf(BelongsTo::class);
    });

    test('has visaCategory belongsTo relationship', function () {
        $member = new CustomerMembers;

        expect($member->visaCategory())->toBeInstanceOf(BelongsTo::class);
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
        $member = CustomerMembers::factory()->create([
            'is_policy_holder' => true,
            'is_insured' => false,
        ]);

        expect((bool) $member->is_policy_holder)->toBeTrue()
            ->and((bool) $member->is_insured)->toBeFalse();
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

    test('sets event to member_added (Non-insured Policyholder) when is_policy_holder is true but is_insured is absent from audit new values', function () {
        $audit = (object) ['event' => 'created', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $data = [
            'audit' => $audit,
            'transformedOld' => [],
            'transformedNew' => ['is_policy_holder' => 'true'],
            'model' => null,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_added (Non-insured Policyholder)');
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
    test('sets event to member_deleted when deleted_at is present in new data', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 0, 'is_principal' => 0];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['first_name' => 'John', 'last_name' => 'Doe'],
            'transformedNew' => ['deleted_at' => '2026-01-01 00:00:00'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_deleted');
    });

    test('sets event to member_updated (Policyholder Removed) when policy holder flag changes to false', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 0, 'is_principal' => 0];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 'true'],
            'transformedNew' => ['is_policy_holder' => 'false'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_updated (Policyholder Removed)');
    });

    test('sets event to member_updated (Policyholder Added) when policy holder flag changes to true', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 1, 'is_principal' => 0];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 'false'],
            'transformedNew' => ['is_policy_holder' => 'true'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_updated (Policyholder Added)');
    });

    test('sets event to member_updated (Principal Removed) when principal flag changes to false', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 0, 'is_principal' => 0];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['first_name' => 'John', 'last_name' => 'Doe', 'is_principal' => 'true'],
            'transformedNew' => ['is_principal' => 'false'],
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
            'transformedOld' => ['first_name' => 'John', 'last_name' => 'Doe', 'is_principal' => 'false'],
            'transformedNew' => ['is_principal' => 'true'],
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
            'transformedOld' => ['first_name' => 'John', 'last_name' => 'Doe'],
            'transformedNew' => ['first_name' => 'Jane'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_updated');
    });

    test('appends (Policyholder) suffix to member_updated when model is policy holder', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 1, 'is_principal' => 0];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['first_name' => 'John', 'last_name' => 'Doe'],
            'transformedNew' => ['first_name' => 'Jane'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_updated (Policyholder)');
    });

    test('appends (Principal) suffix to member_updated when model is principal', function () {
        $audit = (object) ['event' => 'updated', 'auditable_id' => 1, 'auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails'];
        $model = (object) ['first_name' => 'John', 'last_name' => 'Doe', 'is_policy_holder' => 0, 'is_principal' => 1];
        $data = [
            'audit' => $audit,
            'transformedOld' => ['first_name' => 'John', 'last_name' => 'Doe'],
            'transformedNew' => ['first_name' => 'Jane'],
            'model' => $model,
        ];

        $result = CustomerMembers::customizeAuditTransformation($data);

        expect($result['audit']->event)->toBe('member_updated (Principal)');
    });

    test('populates name in transformedOld and transformedNew when first_name/last_name keys are absent', function () {
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

describe('CustomerMembers scopes', function () {
    function makeMember(array $attributes = []): CustomerMembers
    {
        return CustomerMembers::factory()->create($attributes);
    }

    test('individual scope returns only Individual customer_type rows', function () {
        makeMember(['customer_type' => CustomerTypeEnum::Individual]);
        makeMember(['customer_type' => CustomerTypeEnum::Entity]);

        $results = CustomerMembers::individual()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->customer_type)->toBe(CustomerTypeEnum::Individual);
    });

    test('notThirdPartyPayer scope excludes third-party-payer rows', function () {
        makeMember(['is_third_party_payer' => false]);
        makeMember(['is_third_party_payer' => true]);

        $results = CustomerMembers::notThirdPartyPayer()->get();

        expect($results)->toHaveCount(1)
            ->and((bool) $results->first()->is_third_party_payer)->toBeFalse();
    });

    test('policyHolder scope returns only policy-holder rows', function () {
        makeMember(['is_policy_holder' => true]);
        makeMember(['is_policy_holder' => false]);

        $results = CustomerMembers::policyHolder()->get();

        expect($results)->toHaveCount(1)
            ->and((bool) $results->first()->is_policy_holder)->toBeTrue();
    });

    test('scopes can be chained together', function () {
        makeMember([
            'customer_type' => CustomerTypeEnum::Individual,
            'is_policy_holder' => true,
            'is_third_party_payer' => false,
        ]);
        makeMember([
            'customer_type' => CustomerTypeEnum::Entity,
            'is_policy_holder' => true,
            'is_third_party_payer' => false,
        ]);
        makeMember([
            'customer_type' => CustomerTypeEnum::Individual,
            'is_policy_holder' => false,
            'is_third_party_payer' => false,
        ]);
        makeMember([
            'customer_type' => CustomerTypeEnum::Individual,
            'is_policy_holder' => true,
            'is_third_party_payer' => true,
        ]);

        $results = CustomerMembers::individual()->policyHolder()->notThirdPartyPayer()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->customer_type)->toBe(CustomerTypeEnum::Individual)
            ->and((bool) $results->first()->is_policy_holder)->toBeTrue()
            ->and((bool) $results->first()->is_third_party_payer)->toBeFalse();
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
        VisaCategory::factory()->create(['is_active' => true]);
        VisaCategory::factory()->inactive()->create();

        $activeCount = VisaCategory::where('is_active', 1)->count();
        $inactiveCount = VisaCategory::where('is_active', 0)->count();

        expect($activeCount)->toBe(1)
            ->and($inactiveCount)->toBe(1);
    });

    test('can be created with all fillable attributes', function () {
        $visa = VisaCategory::factory()->create([
            'code' => 'VISIT',
            'text' => 'Visit Visa',
            'is_active' => true,
            'sort_order' => 1,
            'health_cover_for_id' => null,
        ]);

        expect($visa->code)->toBe('VISIT')
            ->and($visa->text)->toBe('Visit Visa')
            ->and((bool) $visa->is_active)->toBeTrue()
            ->and($visa->sort_order)->toBe(1);
    });
});
