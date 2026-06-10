<?php

use App\Enums\PermissionsEnum;
use App\Enums\TeamCategoryEnum;
use App\Http\Middleware\PreventRequestForgery;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $db = DB::connection('sqlite');

    $this->admin = TestDataSeeder::createAdminUser(['email' => fake()->unique()->safeEmail()], [
        PermissionsEnum::TeamThresholdView,
        PermissionsEnum::TEAM_ALLOCATION_THRESHOLD_EDIT,
    ]);

    $this->actingAs($this->admin);
    $this->withoutMiddleware(PreventRequestForgery::class);

    // Ensure teams table has category column (required for fetchTeamByPriceAndCategory)
    if ($db->getSchemaBuilder()->hasTable('teams')) {
        if (! $db->getSchemaBuilder()->hasColumn('teams', 'category')) {
            $db->getSchemaBuilder()->table('teams', function ($table) {
                $table->string('category')->nullable()->after('allocation_threshold_enabled');
            });
        }
    }
});

it('updates team allocation threshold', function () {
    $teams = Team::factory()->createForSqlite();

    $payload = [
        'category' => TeamCategoryEnum::AUH->value,
        'teams' => [
            [
                'team_id' => $teams->id,
                'min' => 100,
                'max' => 500,
            ],
        ],
    ];

    $response = $this->postJson('/update-team-allocation-threshold', $payload);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Allocation Threshold updated successfully',
        ]);

    $this->assertDatabaseHas('teams', [
        'id' => $teams->id,
        'min_price' => 100,
        'max_price' => 500,
        'allocation_threshold_enabled' => true,
    ]);
});
