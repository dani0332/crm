<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Services\GroupMedical\GroupMedicalAmtFormDropdownService;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('group health plan types are filtered to type group when column exists', function () {
    DB::table('health_plan_type')->insert([
        ['text' => 'Individual Plan', 'type' => 'individual', 'emirates_id' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['text' => 'Group Plan A', 'type' => 'Group', 'emirates_id' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['text' => 'Group Plan B', 'type' => 'group', 'emirates_id' => 2, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $plans = app(GroupMedicalAmtFormDropdownService::class)->groupHealthPlanTypes();

    expect($plans)->toHaveCount(2)
        ->and($plans->pluck('text')->all())->toEqual(['Group Plan A', 'Group Plan B'])
        ->and($plans->every(fn ($plan) => property_exists($plan, 'emirates_id')))->toBeTrue();
});

test('group health plan types expose emirates_id for frontend filtering', function () {
    DB::table('health_plan_type')->insert([
        'text' => 'Dubai Group Plan',
        'type' => 'group',
        'emirates_id' => 3,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $plan = app(GroupMedicalAmtFormDropdownService::class)
        ->groupHealthPlanTypes()
        ->firstWhere('text', 'Dubai Group Plan');

    expect($plan)->not->toBeNull()
        ->and((int) $plan->emirates_id)->toBe(3);
});

test('insurance providers use group medical quote type mapping', function () {
    $providerId = DB::table('insurance_provider')->insertGetId([
        'code' => 'GM-INS',
        'text' => 'GM Insurer',
        'is_active' => 1,
        'is_deleted' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('insurance_provider_quote_type')->insert([
        'insurance_provider_id' => $providerId,
        'quote_type_id' => QuoteTypeId::GroupMedical,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $providers = app(GroupMedicalAmtFormDropdownService::class)->groupMedicalInsuranceProviders();

    expect($providers)->toHaveCount(1)
        ->and($providers->first()->text)->toBe('GM Insurer');
});

test('group medical categories are loaded from group_medical_category table', function () {
    $categoryId = DB::table('group_medical_category')->insertGetId([
        'text' => 'Category A',
        'sort_order' => 1,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $categories = app(GroupMedicalAmtFormDropdownService::class)->groupMedicalCategories();

    expect($categories)->toHaveCount(1)
        ->and($categories->first()->text)->toBe('Category A')
        ->and($categories->first()->id)->toBe($categoryId);
});

test('form dropdown props include company activity types and gm networks', function () {
    $activityId = DB::table('company_activity_type')->insertGetId([
        'text' => 'Trading',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $networkId = DB::table('group_medical_networks')->insertGetId([
        'text' => 'Network A',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $props = app(GroupMedicalAmtFormDropdownService::class)->formDropdownProps();

    expect($props)->toHaveKeys([
        'companyActivityTypes',
        'healthPlanTypes',
        'insuranceProviders',
        'groupMedicalCategories',
        'healthThirdPartyAdministrators',
        'groupMedicalNetworks',
    ])
        ->and($props['companyActivityTypes']->pluck('id')->all())->toContain($activityId)
        ->and($props['groupMedicalNetworks']->pluck('id')->all())->toContain($networkId);
});
