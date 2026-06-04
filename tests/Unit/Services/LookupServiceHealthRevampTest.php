<?php

declare(strict_types=1);

use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Services\LookupService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
    Cache::flush();
    $this->lookupService = app(LookupService::class);
});

describe('LookupService::getGender', function () {
    test('returns lookups with gender key', function () {
        DB::table('lookups')->insert([
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
    test('returns health insure options with database text', function () {
        DB::table('lookups')->insert([
            ['key' => LookupsEnum::HEALTH_INSURE_OPTIONS->value, 'code' => 'ONLY_MYSELF', 'text' => 'raw_self', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => LookupsEnum::HEALTH_INSURE_OPTIONS->value, 'code' => 'ONLY_MY_FAMILY_MEMBERS', 'text' => 'raw_family', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => LookupsEnum::HEALTH_INSURE_OPTIONS->value, 'code' => 'MYSELF_AND_MY_FAMILY_MEMBERS', 'text' => 'raw_both', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = $this->lookupService->getHealthInsureOptions();

        expect($results)->toHaveCount(3);

        $byCode = $results->keyBy('code');
        expect($byCode['ONLY_MYSELF']->text)->toBe('raw_self')
            ->and($byCode['ONLY_MY_FAMILY_MEMBERS']->text)->toBe('raw_family')
            ->and($byCode['MYSELF_AND_MY_FAMILY_MEMBERS']->text)->toBe('raw_both');
    });

    test('returns empty collection when no health insure options exist', function () {
        $results = $this->lookupService->getHealthInsureOptions();

        expect($results)->toHaveCount(0);
    });
});

describe('LookupService::getPolicyHolder', function () {
    test('returns policy holder options with database text', function () {
        DB::table('lookups')->insert([
            ['key' => LookupsEnum::POLICY_HOLDER_OPTIONS->value, 'code' => 'ME', 'text' => 'raw_me', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => LookupsEnum::POLICY_HOLDER_OPTIONS->value, 'code' => 'OTHER_ADULT_FAMILY_MEMBER', 'text' => 'raw_other', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = $this->lookupService->getPolicyHolder();

        expect($results)->toHaveCount(2);

        $byCode = $results->keyBy('code');
        expect($byCode['ME']->text)->toBe('raw_me')
            ->and($byCode['OTHER_ADULT_FAMILY_MEMBER']->text)->toBe('raw_other');
    });
});

describe('LookupService::getHealthInsureOptions and getPolicyHolder enum labels by source', function () {
    test('applies customer-centric text when lead source is IMCRM or RENEWAL_UPLOAD', function () {
        DB::table('lookups')->insert([
            ['key' => LookupsEnum::HEALTH_INSURE_OPTIONS->value, 'code' => 'ONLY_MYSELF', 'text' => 'raw', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $svc = $this->lookupService;
        expect($svc->getHealthInsureOptions(LeadSourceEnum::IMCRM)->first()->text)->toBe('Only the customer')
            ->and($svc->getHealthInsureOptions(LeadSourceEnum::RENEWAL_UPLOAD)->first()->text)->toBe('Only the customer')
            ->and($svc->getHealthInsureOptions('ECOM')->first()->text)->toBe('raw');
        DB::table('lookups')->where('key', LookupsEnum::HEALTH_INSURE_OPTIONS->value)->delete();
        Cache::flush();

        DB::table('lookups')->insert([
            ['key' => LookupsEnum::POLICY_HOLDER_OPTIONS->value, 'code' => 'ME', 'text' => 'raw_ph', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        expect($svc->getPolicyHolder(LeadSourceEnum::IMCRM)->first()->text)->toBe('The customer')
            ->and($svc->getPolicyHolder(LeadSourceEnum::RENEWAL_UPLOAD)->first()->text)->toBe('The customer')
            ->and($svc->getPolicyHolder('ECOM')->first()->text)->toBe('raw_ph');
    });
});

describe('LookupService::getHealthInsureOptions IMCRM label edge cases', function () {
    test('rows with null code are returned unchanged when source is IMCRM', function () {
        DB::table('lookups')->insert([
            ['key' => LookupsEnum::HEALTH_INSURE_OPTIONS->value, 'code' => null, 'text' => 'no_code', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = $this->lookupService->getHealthInsureOptions(LeadSourceEnum::IMCRM);

        expect($result->first()->text)->toBe('no_code');
    });

    test('rows with unrecognised code are returned unchanged when source is IMCRM', function () {
        DB::table('lookups')->insert([
            ['key' => LookupsEnum::HEALTH_INSURE_OPTIONS->value, 'code' => 'UNKNOWN_CODE', 'text' => 'raw_unknown', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = $this->lookupService->getHealthInsureOptions(LeadSourceEnum::IMCRM);

        expect($result->first()->text)->toBe('raw_unknown');
    });

    test('original cached rows are not mutated by IMCRM label replacement', function () {
        DB::table('lookups')->insert([
            ['key' => LookupsEnum::HEALTH_INSURE_OPTIONS->value, 'code' => 'ONLY_MYSELF', 'text' => 'raw_self', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->lookupService->getHealthInsureOptions(LeadSourceEnum::IMCRM);
        Cache::flush();

        // Re-fetch without IMCRM – should still get raw DB text, not the mutated label
        DB::table('lookups')->where('key', LookupsEnum::HEALTH_INSURE_OPTIONS->value)->update(['text' => 'raw_self']);
        $result = $this->lookupService->getHealthInsureOptions(null);

        expect($result->first()->text)->toBe('raw_self');
    });
});

describe('LookupService::getPolicyHolder IMCRM label edge cases', function () {
    test('rows with unrecognised code are returned unchanged when source is IMCRM', function () {
        DB::table('lookups')->insert([
            ['key' => LookupsEnum::POLICY_HOLDER_OPTIONS->value, 'code' => 'UNKNOWN', 'text' => 'raw_unknown', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = $this->lookupService->getPolicyHolder(LeadSourceEnum::IMCRM);

        expect($result->first()->text)->toBe('raw_unknown');
    });

    test('non-IMCRM non-RENEWAL_UPLOAD source always returns raw database text', function (?string $source) {
        DB::table('lookups')->insert([
            ['key' => LookupsEnum::POLICY_HOLDER_OPTIONS->value, 'code' => 'ME', 'text' => 'raw_me', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        expect($this->lookupService->getPolicyHolder($source)->first()->text)->toBe('raw_me');
    })->with(['ECOM', 'DIRECT', null]);
});

describe('LookupService::getPolicyHolderCategory', function () {
    test('returns all policy holder category lookups', function () {
        DB::table('lookups')->insert([
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
        DB::table('visa_categories')->insert([
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
        DB::table('visa_categories')->insert([
            ['code' => 'VC1', 'text' => 'Inactive', 'is_active' => 0, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $results = $this->lookupService->getVisaCategory();

        expect($results)->toHaveCount(0);
    });
});

describe('LookupService::getHealthMemberRelations', function () {
    test('returns active health member relations ordered by sort_order', function () {
        DB::table('lookups')->insert([
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
        DB::table('lookups')->insert([
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
