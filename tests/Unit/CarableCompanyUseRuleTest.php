<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Enums\RuleEnum;
use App\Enums\RuleTypeEnum;
use App\Models\User;
use App\Pipes\Allocation\Car\Carable;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

final class CarableCompanyUseTestSubject
{
    use Carable;

    public function fetchVehicleUseRules(object $lead): mixed
    {
        return $this->getRulesForVehicleUse($lead);
    }
}

beforeEach(function () {
    TestSchemaCreator::createRulesSchema();

    $db = DB::connection('sqlite');
    $db->table('rule_users')->delete();
    $db->table('rule_details')->delete();
    $db->table('rules')->delete();
});

it('loads company use vehicle rules regardless of lead vehicle_use value', function (string $vehicleUse) {
    $db = DB::connection('sqlite');
    $user = User::factory()->create();

    $ruleId = $db->table('rules')->insertGetId([
        'name' => RuleEnum::COMPANY_USE->value,
        'rule_start_date' => null,
        'rule_end_date' => null,
        'is_active' => 1,
        'rule_type' => (int) RuleTypeEnum::VEHICLE_USE,
        'quote_type_id' => QuoteTypeId::Car,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_details')->insert([
        'rule_id' => $ruleId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_users')->insert([
        'rule_id' => $ruleId,
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $lead = (object) [
        'uuid' => 'lead-uuid-test',
        'source' => 'Test Source',
        'vehicle_use' => $vehicleUse,
    ];

    $subject = new CarableCompanyUseTestSubject;
    $result = $subject->fetchVehicleUseRules($lead);

    expect($result)->not->toBeEmpty();
    $row = $result->first();
    $userIds = explode(',', (string) $row->leadSourceUsers);
    expect($userIds)->toContain((string) $user->id);
})->with([
    'private' => ['private'],
    'commercial' => ['commercial'],
]);

it('does not match private use vehicle rules when resolving company use allocation rules', function () {
    $db = DB::connection('sqlite');
    $user = User::factory()->create();

    $ruleId = $db->table('rules')->insertGetId([
        'name' => RuleEnum::PRIVATE_USE->value,
        'rule_start_date' => null,
        'rule_end_date' => null,
        'is_active' => 1,
        'rule_type' => (int) RuleTypeEnum::VEHICLE_USE,
        'quote_type_id' => QuoteTypeId::Car,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_details')->insert([
        'rule_id' => $ruleId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $db->table('rule_users')->insert([
        'rule_id' => $ruleId,
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $lead = (object) [
        'uuid' => 'lead-uuid-test',
        'source' => 'Test Source',
        'vehicle_use' => 'private',
    ];

    $subject = new CarableCompanyUseTestSubject;
    $result = $subject->fetchVehicleUseRules($lead);

    expect($result)->toBeEmpty();
});
