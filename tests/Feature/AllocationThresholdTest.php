<?php

use App\Enums\TeamCategoryEnum;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllocationThresholdTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_updates_team_allocation_threshold()
    {
        $teams = Team::factory()->count(2)->create();

        $payload = [
            'category' => TeamCategoryEnum::AUH->value,
            'teams' => [
                [
                    'id' => $teams[0]->id,
                    'min' => 100,
                    'max' => 500,
                ],
                [
                    'id' => $teams[1]->id,
                    'min' => 200,
                    'max' => 600,
                ],
            ],
        ];

        $response = $this->postJson('/update-team-allocation-threshold', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Allocation Threshold updated successfully',
            ]);

        $this->assertDatabaseHas('teams', [
            'id' => $teams[0]->id,
            'min_price' => 100,
            'max_price' => 500,
            'allocation_threshold_enabled' => true,
        ]);

        $this->assertDatabaseHas('teams', [
            'id' => $teams[1]->id,
            'min_price' => 200,
            'max_price' => 600,
            'allocation_threshold_enabled' => true,
        ]);
    }
}
