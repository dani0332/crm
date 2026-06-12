<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Enums\RuleEnum;
use App\Enums\RuleTypeEnum;
use App\Models\Rule;
use Database\Seeders\UpdateRuleNameSeederCompany;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRulesSchema();

    $db = DB::connection('sqlite');
    $db->table('rule_users')->delete();
    $db->table('rule_details')->delete();
    $db->table('rules')->delete();
});

it('renames an active commercial vehicle use rule to company use', function () {
    $rule = Rule::create([
        'name' => RuleEnum::COMMERCIAL_USE->value,
        'rule_type' => RuleTypeEnum::VEHICLE_USE,
        'is_active' => 1,
        'quote_type_id' => QuoteTypeId::Car,
    ]);

    (new UpdateRuleNameSeederCompany)->run();

    $rule->refresh();

    expect($rule->name)->toBe(RuleEnum::COMPANY_USE->value);
});

it('creates a company use vehicle use rule when no active commercial rule exists', function () {
    expect(Rule::query()->count())->toBe(0);

    (new UpdateRuleNameSeederCompany)->run();

    $rule = Rule::query()
        ->where('name', RuleEnum::COMPANY_USE->value)
        ->where('rule_type', RuleTypeEnum::VEHICLE_USE)
        ->where('is_active', 1)
        ->first();

    expect($rule)->not->toBeNull()
        ->and((int) $rule->quote_type_id)->toBe(QuoteTypeId::Car);

    expect(
        DB::table('rule_details')->where('rule_id', $rule->id)->exists()
    )->toBeTrue();
});

it('is idempotent when company use rule already exists from a previous run', function () {
    (new UpdateRuleNameSeederCompany)->run();
    $countAfterFirst = Rule::query()->count();

    (new UpdateRuleNameSeederCompany)->run();
    $countAfterSecond = Rule::query()->count();

    expect($countAfterSecond)->toBe($countAfterFirst);
});
