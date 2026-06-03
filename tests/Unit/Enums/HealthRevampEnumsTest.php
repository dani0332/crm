<?php

declare(strict_types=1);

use App\Enums\GenericRequestEnum;
use App\Enums\HealthCoverForEnum;
use App\Enums\HealthInsureEnum;
use App\Enums\HealthPolicyHolderEnum;
use App\Enums\LookupsEnum;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

describe('HealthInsureEnum', function () {
    test('has correct case values', function () {
        expect(HealthInsureEnum::ONLY_MYSELF->value)->toBe('ONLY_MYSELF')
            ->and(HealthInsureEnum::ONLY_MY_FAMILY_MEMBERS->value)->toBe('ONLY_MY_FAMILY_MEMBERS')
            ->and(HealthInsureEnum::MYSELF_AND_MY_FAMILY_MEMBERS->value)->toBe('MYSELF_AND_MY_FAMILY_MEMBERS');
    });

    test('getLabel returns correct label for each case', function (HealthInsureEnum $case, string $expectedLabel) {
        expect($case->getLabel())->toBe($expectedLabel);
    })->with([
        [HealthInsureEnum::ONLY_MYSELF, 'Only the customer'],
        [HealthInsureEnum::ONLY_MY_FAMILY_MEMBERS, "Only the customer's family member(s)"],
        [HealthInsureEnum::MYSELF_AND_MY_FAMILY_MEMBERS, 'The customer and their family member(s)'],
    ]);

    test('can be instantiated from string value', function () {
        expect(HealthInsureEnum::from('ONLY_MYSELF'))->toBe(HealthInsureEnum::ONLY_MYSELF)
            ->and(HealthInsureEnum::from('ONLY_MY_FAMILY_MEMBERS'))->toBe(HealthInsureEnum::ONLY_MY_FAMILY_MEMBERS)
            ->and(HealthInsureEnum::from('MYSELF_AND_MY_FAMILY_MEMBERS'))->toBe(HealthInsureEnum::MYSELF_AND_MY_FAMILY_MEMBERS);
    });

    test('tryFrom returns null for invalid value', function () {
        expect(HealthInsureEnum::tryFrom('INVALID_VALUE'))->toBeNull();
    });

    test('has exactly three cases', function () {
        expect(HealthInsureEnum::cases())->toHaveCount(3);
    });
});

describe('HealthPolicyHolderEnum', function () {
    test('has correct case values', function () {
        expect(HealthPolicyHolderEnum::ME->value)->toBe('ME')
            ->and(HealthPolicyHolderEnum::OTHER_ADULT_FAMILY_MEMBER->value)->toBe('OTHER_ADULT_FAMILY_MEMBER');
    });

    test('getLabel returns correct label for each case', function (HealthPolicyHolderEnum $case, string $expectedLabel) {
        expect($case->getLabel())->toBe($expectedLabel);
    })->with([
        [HealthPolicyHolderEnum::ME, 'The customer'],
        [HealthPolicyHolderEnum::OTHER_ADULT_FAMILY_MEMBER, 'Another adult family member'],
    ]);

    test('can be instantiated from string value', function () {
        expect(HealthPolicyHolderEnum::from('ME'))->toBe(HealthPolicyHolderEnum::ME)
            ->and(HealthPolicyHolderEnum::from('OTHER_ADULT_FAMILY_MEMBER'))->toBe(HealthPolicyHolderEnum::OTHER_ADULT_FAMILY_MEMBER);
    });

    test('tryFrom returns null for invalid value', function () {
        expect(HealthPolicyHolderEnum::tryFrom('INVALID'))->toBeNull();
    });

    test('has exactly two cases', function () {
        expect(HealthPolicyHolderEnum::cases())->toHaveCount(2);
    });
});

describe('HealthCoverForEnum', function () {
    test('has correct integer case values', function () {
        expect(HealthCoverForEnum::MY_COMPANY->value)->toBe(3)
            ->and(HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value)->toBe(4)
            ->and(HealthCoverForEnum::DOMESTIC_HELPER->value)->toBe(5);
    });

    test('can be instantiated from integer value', function () {
        expect(HealthCoverForEnum::from(3))->toBe(HealthCoverForEnum::MY_COMPANY)
            ->and(HealthCoverForEnum::from(4))->toBe(HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES)
            ->and(HealthCoverForEnum::from(5))->toBe(HealthCoverForEnum::DOMESTIC_HELPER);
    });

    test('tryFrom returns null for invalid value', function () {
        expect(HealthCoverForEnum::tryFrom(999))->toBeNull();
    });

    test('has all cover-for cases (inactive and active)', function () {
        expect(HealthCoverForEnum::cases())->toHaveCount(5);
    });
});

describe('GenericRequestEnum gender constants', function () {
    test('short and legacy gender codes have correct string values', function () {
        expect(GenericRequestEnum::MALE_SINGLE_VALUE)->toBe('M')
            ->and(GenericRequestEnum::FEMALE_SHORT_VALUE)->toBe('F')
            ->and(GenericRequestEnum::MALE_SINGLE)->toBe('Male')
            ->and(GenericRequestEnum::FEMALE)->toBe('Female')
            ->and(GenericRequestEnum::FEMALE_SINGLE_VALUE)->toBe('FS')
            ->and(GenericRequestEnum::FEMALE_MARRIED_VALUE)->toBe('FM');
    });
});

describe('LookupsEnum health revamp cases', function () {
    test('has new health revamp lookup keys', function () {
        expect(LookupsEnum::HEALTH_INSURE_OPTIONS->value)->toBe('health_insure_options')
            ->and(LookupsEnum::POLICY_HOLDER_OPTIONS->value)->toBe('policy_holder_options')
            ->and(LookupsEnum::POLICY_HOLDER_CATEGORY->value)->toBe('policy_holder_category')
            ->and(LookupsEnum::GENDER->value)->toBe('gender')
            ->and(LookupsEnum::HEALTH_MEMBER_RELATION->value)->toBe('health-member-relation')
            ->and(LookupsEnum::DOMESTIC_WORKER_RELATION->value)->toBe('domestic-worker-relation');
    });

    test('can instantiate health revamp cases from string values', function () {
        expect(LookupsEnum::from('health_insure_options'))->toBe(LookupsEnum::HEALTH_INSURE_OPTIONS)
            ->and(LookupsEnum::from('policy_holder_options'))->toBe(LookupsEnum::POLICY_HOLDER_OPTIONS)
            ->and(LookupsEnum::from('policy_holder_category'))->toBe(LookupsEnum::POLICY_HOLDER_CATEGORY)
            ->and(LookupsEnum::from('gender'))->toBe(LookupsEnum::GENDER)
            ->and(LookupsEnum::from('health-member-relation'))->toBe(LookupsEnum::HEALTH_MEMBER_RELATION)
            ->and(LookupsEnum::from('domestic-worker-relation'))->toBe(LookupsEnum::DOMESTIC_WORKER_RELATION);
    });
});
