<?php

use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $this->admin = TestDataSeeder::createAdminUser(['email' => fake()->unique()->safeEmail()], [
        PermissionsEnum::TeamThresholdView,
        PermissionsEnum::TEAM_ALLOCATION_THRESHOLD_EDIT,
    ]);

    $this->actingAs($this->admin);
    $this->withoutMiddleware(PreventRequestForgery::class);

    $db = DB::connection('sqlite');

    // Create all teams that should be included in the allocation threshold
    $this->ebpTeam = $db->table('teams')->insertGetId([
        'name' => quoteTypeCode::EBP,
        'code' => quoteTypeCode::EBP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 0,
        'max_price' => 1000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->rmSpeedTeam = $db->table('teams')->insertGetId([
        'name' => quoteTypeCode::RM_SPEED,
        'code' => quoteTypeCode::RM_SPEED,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 1001,
        'max_price' => 2000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->rmNbTeam = $db->table('teams')->insertGetId([
        'name' => quoteTypeCode::RM_NB,
        'code' => quoteTypeCode::RM_NB,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 2001,
        'max_price' => 3000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->pcpTeam = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::PCP,
        'code' => TeamNameEnum::PCP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 3001,
        'max_price' => 4000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->gbpTeam = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::GBP,
        'code' => TeamNameEnum::GBP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'min_price' => 4001,
        'max_price' => 5000,
        'allocation_threshold_enabled' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create a team that should NOT be included (different type)
    $this->excludedTeam = $db->table('teams')->insertGetId([
        'name' => 'Excluded Team',
        'code' => 'Excluded Team',
        'type' => TeamTypeEnum::PRODUCT,
        'is_active' => 1,
        'parent_team_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('returns all teams including GBP team in the correct order', function () {
    $response = $this->get(route('allocation-threshold.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('teams', 4)
        ->where('teams.0.name', quoteTypeCode::EBP)
        ->where('teams.1.name', quoteTypeCode::RM_SPEED)
        ->where('teams.2.name', quoteTypeCode::RM_NB)
        ->where('teams.3.name', TeamNameEnum::GBP)
    );
});

it('includes GBP team in the teams list', function () {
    $response = $this->get(route('allocation-threshold.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('teams', 4)
        ->where('teams.3.name', TeamNameEnum::GBP)
    );
});

it('sorts teams according to custom sequence with GBP at the end', function () {
    $response = $this->get(route('allocation-threshold.index'));

    $response->assertSuccessful();
    $teams = $response->original->getData()['page']['props']['teams'];

    expect($teams)->toHaveCount(4);
    expect($teams[0]['name'])->toBe(quoteTypeCode::EBP);
    expect($teams[1]['name'])->toBe(quoteTypeCode::RM_SPEED);
    expect($teams[2]['name'])->toBe(quoteTypeCode::RM_NB);
    expect($teams[3]['name'])->toBe(TeamNameEnum::GBP);
});

it('excludes teams that are not of type TEAM', function () {
    $response = $this->get(route('allocation-threshold.index'));

    $response->assertSuccessful();
    $teams = $response->original->getData()['page']['props']['teams'];

    $teamNames = collect($teams)->pluck('name')->toArray();
    expect($teamNames)->not->toContain('Excluded Team');
});

it('only includes teams with names in the specified list', function () {
    $response = $this->get(route('allocation-threshold.index'));

    $response->assertSuccessful();
    $teams = $response->original->getData()['page']['props']['teams'];

    $expectedTeamNames = [
        quoteTypeCode::EBP,
        quoteTypeCode::RM_SPEED,
        quoteTypeCode::RM_NB,
        TeamNameEnum::GBP,
    ];

    $teamNames = collect($teams)->pluck('name')->toArray();
    foreach ($teamNames as $teamName) {
        expect($expectedTeamNames)->toContain($teamName);
    }
});

it('requires TeamThresholdView permission to access index', function () {
    $userWithoutPermission = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()]);
    $this->actingAs($userWithoutPermission);

    $response = $this->get(route('allocation-threshold.index'));

    $response->assertForbidden();
});

it('forbids update when user only has TeamThresholdView permission', function () {
    $viewer = TestDataSeeder::createUserWithRole('ThresholdViewer', ['email' => fake()->unique()->safeEmail()]);
    TestDataSeeder::seedRolePermissions('ThresholdViewer', [PermissionsEnum::TeamThresholdView]);

    $this->actingAs($viewer);

    $response = $this->postJson('/update-team-allocation-threshold', [
        'category' => 'AUH',
        'teams' => [
            [
                'team_id' => $this->gbpTeam,
                'min' => 1,
                'max' => 100,
            ],
        ],
    ]);

    $response->assertForbidden();
});

it('updates GBP team allocation threshold with min 30k and max 10 million', function () {
    $db = DB::connection('sqlite');
    $minPrice = 30000;
    $maxPrice = 10000000;

    $response = $this->postJson('/update-team-allocation-threshold', [
        'teams' => [
            [
                'team_id' => $this->gbpTeam,
                'min' => $minPrice,
                'max' => $maxPrice,
            ],
        ],
    ]);

    $response->assertSuccessful();
    $response->assertJson(['message' => 'Allocation Threshold updated successfully']);

    // Verify the GBP team was updated in the database
    $gbpTeam = $db->table('teams')->where('id', $this->gbpTeam)->first();
    expect((float) $gbpTeam->min_price)->toBe((float) $minPrice);
    expect((float) $gbpTeam->max_price)->toBe((float) $maxPrice);
    expect((int) $gbpTeam->allocation_threshold_enabled)->toBe(1);
});

it('updates GBP team allocation threshold and verifies it appears in index', function () {

    $minPrice = 30000;
    $maxPrice = 10000000;

    // Update GBP team
    $this->postJson('/update-team-allocation-threshold', [
        'teams' => [
            [
                'team_id' => $this->gbpTeam,
                'min' => $minPrice,
                'max' => $maxPrice,
            ],
        ],
    ]);

    // Verify updated values appear in index response
    $response = $this->get(route('allocation-threshold.index'));

    $response->assertSuccessful();
    $teams = $response->original->getData()['page']['props']['teams'];

    $gbpTeam = collect($teams)->firstWhere('name', TeamNameEnum::GBP);
    expect($gbpTeam)->not->toBeNull();
    expect((float) $gbpTeam['min_price'])->toBe((float) $minPrice);
    expect((float) $gbpTeam['max_price'])->toBe((float) $maxPrice);
    expect((int) $gbpTeam['allocation_threshold_enabled'])->toBe(1);
});
