<?php

declare(strict_types=1);

use App\Enums\LookupsEnum;
use App\Services\LookupService;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->lookupService = app(LookupService::class);
});

describe('LookupService::getGender', function () {
    test('returns lookups with gender key', function () {
        DB::connection('sqlite')->table('lookups')->insert([
            ['key' => LookupsEnum::GENDER->value, 'code' => 'M', 'text' => 'Male', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => LookupsEnum::GENDER->value, 'code' => 'F', 'text' => 'Female', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'other_key', 'code' => 'X', 'text' => 'Other', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = $this->lookupService->getGender();

        expect($results)->toHaveCount(2)
            ->and($results->pluck('code')->toArray())->toContain('M', 'F');
    });

    test('returns empty collection when no gender lookups exist', function () {
        $results = $this->lookupService->getGender();

        expect($results)->toHaveCount(0);
    });
});

describe('LookupService::getHealthInsureOptions', function () {
    test('returns health insure options with translated labels', function () {
        DB::connection('sqlite')->table('lookups')->insert([
            ['key' => LookupsEnum::HEALTH_INSURE_OPTIONS->value, 'code' => 'ONLY_MYSELF', 'text' => 'raw', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => LookupsEnum::HEALTH_INSURE_OPTIONS->value, 'code' => 'ONLY_MY_FAMILY_MEMBERS', 'text' => 'raw', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => LookupsEnum::HEALTH_INSURE_OPTIONS->value, 'code' => 'MYSELF_AND_MY_FAMILY_MEMBERS', 'text' => 'raw', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = $this->lookupService->getHealthInsureOptions();

        expect($results)->toHaveCount(3);

        $byCode = $results->keyBy('code');
        expect($byCode['ONLY_MYSELF']->text)->toBe('Only the customer')
            ->and($byCode['ONLY_MY_FAMILY_MEMBERS']->text)->toBe("Only the customer\u{2019}s family member(s)")
            ->and($byCode['MYSELF_AND_MY_FAMILY_MEMBERS']->text)->toBe('The customer and their family member(s)');
    });

    test('returns empty collection when no health insure options exist', function () {
        $results = $this->lookupService->getHealthInsureOptions();

        expect($results)->toHaveCount(0);
    });
});

describe('LookupService::getPolicyHolder', function () {
    test('returns policy holder options with translated labels', function () {
        DB::connection('sqlite')->table('lookups')->insert([
            ['key' => LookupsEnum::POLICY_HOLDER_OPTIONS->value, 'code' => 'ME', 'text' => 'raw', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => LookupsEnum::POLICY_HOLDER_OPTIONS->value, 'code' => 'OTHER_ADULT_FAMILY_MEMBER', 'text' => 'raw', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = $this->lookupService->getPolicyHolder();

        expect($results)->toHaveCount(2);

        $byCode = $results->keyBy('code');
        expect($byCode['ME']->text)->toBe('The customer')
            ->and($byCode['OTHER_ADULT_FAMILY_MEMBER']->text)->toBe('Another adult family member');
    });
});

describe('LookupService::getPolicyHolderCategory', function () {
    test('returns all policy holder category lookups', function () {
        DB::connection('sqlite')->table('lookups')->insert([
            ['key' => LookupsEnum::POLICY_HOLDER_CATEGORY->value, 'code' => 'CAT_A', 'text' => 'Category A', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => LookupsEnum::POLICY_HOLDER_CATEGORY->value, 'code' => 'CAT_B', 'text' => 'Category B', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = $this->lookupService->getPolicyHolderCategory();

        expect($results)->toHaveCount(2)
            ->and($results->pluck('code')->toArray())->toContain('CAT_A', 'CAT_B');
    });
});

describe('LookupService::getVisaCategory', function () {
    test('returns only active visa categories ordered by sort_order', function () {
        DB::connection('sqlite')->table('visa_categories')->insert([
            ['code' => 'VC1', 'text' => 'Visit Visa', 'is_active' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'VC2', 'text' => 'Work Permit', 'is_active' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'VC3', 'text' => 'Expired', 'is_active' => 0, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = $this->lookupService->getVisaCategory();

        expect($results)->toHaveCount(2)
            ->and($results->first()->code)->toBe('VC2')
            ->and($results->last()->code)->toBe('VC1');
    });

    test('returns empty collection when no active visa categories exist', function () {
        DB::connection('sqlite')->table('visa_categories')->insert([
            ['code' => 'VC1', 'text' => 'Inactive', 'is_active' => 0, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = $this->lookupService->getVisaCategory();

        expect($results)->toHaveCount(0);
    });
});

describe('LookupService::getHealthMemberRelations', function () {
    test('returns active health member relations ordered by sort_order', function () {
        DB::connection('sqlite')->table('lookups')->insert([
            ['key' => LookupsEnum::HEALTH_MEMBER_RELATION->value, 'code' => 'SPOUSE', 'text' => 'Spouse', 'is_active' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => LookupsEnum::HEALTH_MEMBER_RELATION->value, 'code' => 'CHILD', 'text' => 'Child', 'is_active' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['key' => LookupsEnum::HEALTH_MEMBER_RELATION->value, 'code' => 'PARENT', 'text' => 'Parent', 'is_active' => 0, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = $this->lookupService->getHealthMemberRelations();

        expect($results)->toHaveCount(2)
            ->and($results->pluck('code')->toArray())->toContain('SPOUSE', 'CHILD')
            ->and($results->pluck('code')->toArray())->not->toContain('PARENT');
    });
});

describe('LookupService::getDomesticWorkerRelations', function () {
    test('returns active domestic worker relations ordered by sort_order', function () {
        DB::connection('sqlite')->table('lookups')->insert([
            ['key' => LookupsEnum::DOMESTIC_WORKER_RELATION->value, 'code' => 'MAID', 'text' => 'Maid', 'is_active' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => LookupsEnum::DOMESTIC_WORKER_RELATION->value, 'code' => 'DRIVER', 'text' => 'Driver', 'is_active' => 0, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = $this->lookupService->getDomesticWorkerRelations();

        expect($results)->toHaveCount(1)
            ->and($results->first()->code)->toBe('MAID');
    });

    test('returns empty collection when no active domestic worker relations exist', function () {
        $results = $this->lookupService->getDomesticWorkerRelations();

        expect($results)->toHaveCount(0);
    });
});
