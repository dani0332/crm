<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Enums\RuleTypeEnum;
use App\Models\User;
use App\Services\RuleService;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRulesSchema();

    $db = DB::connection('sqlite');
    $db->table('rule_users')->delete();
    $db->table('rule_details')->delete();
    $db->table('rules')->delete();
});

it('includes users attached to vehicle use rules for car quote type', function () {
    $db = DB::connection('sqlite');
    $vehicleUseUser = User::factory()->create();
    $leadSourceUser = User::factory()->create();

    $vehicleRuleId = $db->table('rules')->insertGetId([
        'name' => 'Company Use',
        'rule_start_date' => null,
        'rule_end_date' => null,
        'is_active' => 1,
        'rule_type' => (int) RuleTypeEnum::VEHICLE_USE,
        'quote_type_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_details')->insert([
        'rule_id' => $vehicleRuleId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_users')->insert([
        'rule_id' => $vehicleRuleId,
        'user_id' => $vehicleUseUser->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $otherRuleId = $db->table('rules')->insertGetId([
        'name' => 'Lead Source Rule',
        'rule_start_date' => null,
        'rule_end_date' => null,
        'is_active' => 1,
        'rule_type' => (int) RuleTypeEnum::LEAD_SOURCE,
        'quote_type_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_details')->insert([
        'rule_id' => $otherRuleId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_users')->insert([
        'rule_id' => $otherRuleId,
        'user_id' => $leadSourceUser->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = new RuleService;
    $ids = $service->getRuleUserIds(QuoteTypes::CAR);

    expect($ids)->toContain($vehicleUseUser->id)
        ->and($ids)->toContain($leadSourceUser->id);
});

it('excludes users on inactive rules', function () {
    $db = DB::connection('sqlite');
    $activeUser = User::factory()->create();
    $inactiveUser = User::factory()->create();

    $activeRuleId = $db->table('rules')->insertGetId([
        'name' => 'Active Rule',
        'rule_start_date' => null,
        'rule_end_date' => null,
        'is_active' => 1,
        'rule_type' => (int) RuleTypeEnum::LEAD_SOURCE,
        'quote_type_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_details')->insert([
        'rule_id' => $activeRuleId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_users')->insert([
        'rule_id' => $activeRuleId,
        'user_id' => $activeUser->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $inactiveRuleId = $db->table('rules')->insertGetId([
        'name' => 'Inactive Rule',
        'rule_start_date' => null,
        'rule_end_date' => null,
        'is_active' => 0,
        'rule_type' => (int) RuleTypeEnum::VEHICLE_USE,
        'quote_type_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_details')->insert([
        'rule_id' => $inactiveRuleId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_users')->insert([
        'rule_id' => $inactiveRuleId,
        'user_id' => $inactiveUser->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = new RuleService;
    $ids = $service->getRuleUserIds(QuoteTypes::CAR);

    expect($ids)->toContain($activeUser->id)
        ->and($ids)->not->toContain($inactiveUser->id);
});

it('scopes results to the given quote type', function () {
    $db = DB::connection('sqlite');
    $carUser = User::factory()->create();
    $homeUser = User::factory()->create();

    $carRuleId = $db->table('rules')->insertGetId([
        'name' => 'Car Rule',
        'rule_start_date' => null,
        'rule_end_date' => null,
        'is_active' => 1,
        'rule_type' => (int) RuleTypeEnum::LEAD_SOURCE,
        'quote_type_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_details')->insert([
        'rule_id' => $carRuleId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_users')->insert([
        'rule_id' => $carRuleId,
        'user_id' => $carUser->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $homeRuleId = $db->table('rules')->insertGetId([
        'name' => 'Home Rule',
        'rule_start_date' => null,
        'rule_end_date' => null,
        'is_active' => 1,
        'rule_type' => (int) RuleTypeEnum::LEAD_SOURCE,
        'quote_type_id' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_details')->insert([
        'rule_id' => $homeRuleId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_users')->insert([
        'rule_id' => $homeRuleId,
        'user_id' => $homeUser->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = new RuleService;
    $carIds = $service->getRuleUserIds(QuoteTypes::CAR);

    expect($carIds)->toContain($carUser->id)
        ->and($carIds)->not->toContain($homeUser->id);
});

it('does not return users when rules have no rule_details row', function () {
    $db = DB::connection('sqlite');
    $user = User::factory()->create();

    $ruleId = $db->table('rules')->insertGetId([
        'name' => 'Orphan Rule',
        'rule_start_date' => null,
        'rule_end_date' => null,
        'is_active' => 1,
        'rule_type' => (int) RuleTypeEnum::LEAD_SOURCE,
        'quote_type_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_users')->insert([
        'rule_id' => $ruleId,
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = new RuleService;
    $ids = $service->getRuleUserIds(QuoteTypes::CAR);

    expect($ids)->not->toContain($user->id);
});
