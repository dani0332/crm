<?php

declare(strict_types=1);

namespace Tests\Unit\Observers;

use App\Models\Team;
use Tests\Support\Schema\CoreSchema;
use Tests\TestCase;

class TeamObserverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the teams table exists
        (new CoreSchema)->register();
    }

    public function test_code_column_is_set_to_name_value_when_team_is_created(): void
    {
        $team = Team::create([
            'name' => 'Test Team',
            'type' => 2,
            'is_active' => 1,
        ]);

        $this->assertEquals('Test Team', $team->code);
    }

    public function test_code_column_preserves_custom_value_when_provided(): void
    {
        $team = Team::create([
            'name' => 'Another Team',
            'code' => 'custom_code',
            'type' => 2,
            'is_active' => 1,
        ]);

        $this->assertEquals('custom_code', $team->code);
    }

    public function test_code_column_is_set_for_product_team(): void
    {
        $team = Team::create([
            'name' => 'Car',
            'type' => 1, // Product type
            'is_active' => 1,
        ]);

        $this->assertEquals('Car', $team->code);
    }

    public function test_code_column_is_set_for_sub_team(): void
    {
        $parentTeam = Team::create([
            'name' => 'Parent Team',
            'type' => 2,
            'is_active' => 1,
        ]);

        $subTeam = Team::create([
            'name' => 'Sub Team',
            'type' => 3, // Sub team type
            'parent_team_id' => $parentTeam->id,
            'is_active' => 1,
        ]);

        $this->assertEquals('Sub Team', $subTeam->code);
    }
}