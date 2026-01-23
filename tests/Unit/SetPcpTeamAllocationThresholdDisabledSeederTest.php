<?php

use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use Database\Seeders\SetPcpTeamAllocationThresholdDisabledSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

it('sets allocation_threshold_enabled to false for PCP team', function () {
    $db = DB::connection('sqlite');

    // Create PCP team with allocation_threshold_enabled = true
    $pcpTeamId = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::PCP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 1000,
        'max_price' => 2000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Verify initial state
    $pcpTeam = $db->table('teams')->where('id', $pcpTeamId)->first();
    expect((int) $pcpTeam->allocation_threshold_enabled)->toBe(1);

    // Run the seeder
    $seeder = new SetPcpTeamAllocationThresholdDisabledSeeder;
    $seeder->run();

    // Verify allocation_threshold_enabled is now false
    $pcpTeam = $db->table('teams')->where('id', $pcpTeamId)->first();
    expect((int) $pcpTeam->allocation_threshold_enabled)->toBe(0);
});

it('only updates PCP team and does not affect other teams', function () {
    $db = DB::connection('sqlite');

    // Create PCP team with allocation_threshold_enabled = true
    $pcpTeamId = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::PCP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 1000,
        'max_price' => 2000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create another team with allocation_threshold_enabled = true
    $otherTeamId = $db->table('teams')->insertGetId([
        'name' => 'Other Team',
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 2000,
        'max_price' => 3000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Run the seeder
    $seeder = new SetPcpTeamAllocationThresholdDisabledSeeder;
    $seeder->run();

    // Verify PCP team is updated
    $pcpTeam = $db->table('teams')->where('id', $pcpTeamId)->first();
    expect((int) $pcpTeam->allocation_threshold_enabled)->toBe(0);

    // Verify other team is not affected
    $otherTeam = $db->table('teams')->where('id', $otherTeamId)->first();
    expect((int) $otherTeam->allocation_threshold_enabled)->toBe(1);
});

it('handles multiple PCP teams if they exist', function () {
    $db = DB::connection('sqlite');

    // Create multiple PCP teams (though this shouldn't happen in practice)
    $pcpTeam1Id = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::PCP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 1000,
        'max_price' => 2000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $pcpTeam2Id = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::PCP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 2000,
        'max_price' => 3000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Run the seeder
    $seeder = new SetPcpTeamAllocationThresholdDisabledSeeder;
    $seeder->run();

    // Verify both PCP teams are updated
    $pcpTeam1 = $db->table('teams')->where('id', $pcpTeam1Id)->first();
    expect((int) $pcpTeam1->allocation_threshold_enabled)->toBe(0);

    $pcpTeam2 = $db->table('teams')->where('id', $pcpTeam2Id)->first();
    expect((int) $pcpTeam2->allocation_threshold_enabled)->toBe(0);
});

it('does not fail if PCP team does not exist', function () {
    // Run the seeder when no PCP team exists
    $seeder = new SetPcpTeamAllocationThresholdDisabledSeeder;

    // Should not throw an exception
    expect(fn () => $seeder->run())->not->toThrow(Exception::class);
});

it('only updates PCP teams where allocation_threshold_enabled is true', function () {
    $db = DB::connection('sqlite');

    // Create PCP team with allocation_threshold_enabled = true
    $pcpTeamEnabledId = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::PCP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 1000,
        'max_price' => 2000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create PCP team with allocation_threshold_enabled = false
    $pcpTeamDisabledId = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::PCP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 2000,
        'max_price' => 3000,
        'allocation_threshold_enabled' => 0,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Run the seeder
    $seeder = new SetPcpTeamAllocationThresholdDisabledSeeder;
    $seeder->run();

    // Verify enabled team is updated to false
    $pcpTeamEnabled = $db->table('teams')->where('id', $pcpTeamEnabledId)->first();
    expect((int) $pcpTeamEnabled->allocation_threshold_enabled)->toBe(0);

    // Verify disabled team remains false (not updated)
    $pcpTeamDisabled = $db->table('teams')->where('id', $pcpTeamDisabledId)->first();
    expect((int) $pcpTeamDisabled->allocation_threshold_enabled)->toBe(0);
});

it('sets allocation_threshold_enabled to true for GBP team', function () {
    $db = DB::connection('sqlite');

    // Create GBP team with allocation_threshold_enabled = false
    $gbpTeamId = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::GBP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 30000,
        'max_price' => 10000000,
        'allocation_threshold_enabled' => 0,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Verify initial state
    $gbpTeam = $db->table('teams')->where('id', $gbpTeamId)->first();
    expect((int) $gbpTeam->allocation_threshold_enabled)->toBe(0);

    // Run the seeder
    $seeder = new SetPcpTeamAllocationThresholdDisabledSeeder;
    $seeder->run();

    // Verify allocation_threshold_enabled is now true
    $gbpTeam = $db->table('teams')->where('id', $gbpTeamId)->first();
    expect((int) $gbpTeam->allocation_threshold_enabled)->toBe(1);
});

it('only updates GBP teams where allocation_threshold_enabled is false', function () {
    $db = DB::connection('sqlite');

    // Create GBP team with allocation_threshold_enabled = false
    $gbpTeamDisabledId = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::GBP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 30000,
        'max_price' => 10000000,
        'allocation_threshold_enabled' => 0,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create GBP team with allocation_threshold_enabled = true
    $gbpTeamEnabledId = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::GBP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 30000,
        'max_price' => 10000000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Run the seeder
    $seeder = new SetPcpTeamAllocationThresholdDisabledSeeder;
    $seeder->run();

    // Verify disabled team is updated to true
    $gbpTeamDisabled = $db->table('teams')->where('id', $gbpTeamDisabledId)->first();
    expect((int) $gbpTeamDisabled->allocation_threshold_enabled)->toBe(1);

    // Verify enabled team remains true (not updated)
    $gbpTeamEnabled = $db->table('teams')->where('id', $gbpTeamEnabledId)->first();
    expect((int) $gbpTeamEnabled->allocation_threshold_enabled)->toBe(1);
});

it('updates both PCP and GBP teams correctly', function () {
    $db = DB::connection('sqlite');

    // Create PCP team with allocation_threshold_enabled = true
    $pcpTeamId = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::PCP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 1000,
        'max_price' => 2000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create GBP team with allocation_threshold_enabled = false
    $gbpTeamId = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::GBP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 30000,
        'max_price' => 10000000,
        'allocation_threshold_enabled' => 0,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Run the seeder
    $seeder = new SetPcpTeamAllocationThresholdDisabledSeeder;
    $seeder->run();

    // Verify PCP team is disabled
    $pcpTeam = $db->table('teams')->where('id', $pcpTeamId)->first();
    expect((int) $pcpTeam->allocation_threshold_enabled)->toBe(0);

    // Verify GBP team is enabled
    $gbpTeam = $db->table('teams')->where('id', $gbpTeamId)->first();
    expect((int) $gbpTeam->allocation_threshold_enabled)->toBe(1);
});

it('does not affect other teams when updating PCP and GBP', function () {
    $db = DB::connection('sqlite');

    // Create PCP team with allocation_threshold_enabled = true
    $pcpTeamId = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::PCP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 1000,
        'max_price' => 2000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create GBP team with allocation_threshold_enabled = false
    $gbpTeamId = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::GBP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 30000,
        'max_price' => 10000000,
        'allocation_threshold_enabled' => 0,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create another team with allocation_threshold_enabled = true
    $otherTeamId = $db->table('teams')->insertGetId([
        'name' => 'Other Team',
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 2000,
        'max_price' => 3000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Run the seeder
    $seeder = new SetPcpTeamAllocationThresholdDisabledSeeder;
    $seeder->run();

    // Verify PCP team is disabled
    $pcpTeam = $db->table('teams')->where('id', $pcpTeamId)->first();
    expect((int) $pcpTeam->allocation_threshold_enabled)->toBe(0);

    // Verify GBP team is enabled
    $gbpTeam = $db->table('teams')->where('id', $gbpTeamId)->first();
    expect((int) $gbpTeam->allocation_threshold_enabled)->toBe(1);

    // Verify other team is not affected
    $otherTeam = $db->table('teams')->where('id', $otherTeamId)->first();
    expect((int) $otherTeam->allocation_threshold_enabled)->toBe(1);
});

it('does not fail if GBP team does not exist', function () {
    // Run the seeder when no GBP team exists
    $seeder = new SetPcpTeamAllocationThresholdDisabledSeeder;

    // Should not throw an exception
    expect(fn () => $seeder->run())->not->toThrow(Exception::class);
});
