<?php

namespace Database\Seeders;

use App\Enums\TeamCategoryEnum;
use App\Enums\TeamTypeEnum;
use App\Models\Team;
use Illuminate\Database\Seeder;

class HealthTeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedHealthTeamPEC();
    }

    private function seedHealthTeamPEC(): void
    {
        $healthTeam = Team::where('name', 'Health')->first();

        if ($healthTeam) {
            Team::firstOrCreate([
                'name' => 'PEC',
                'code' => 'PEC',
                'type' => TeamTypeEnum::TEAM,
                'is_active' => 1,
                'parent_team_id' => $healthTeam->id,
                'category' => TeamCategoryEnum::NON_AUH,
                'allocation_threshold_enabled' => true,
                'min_price' => 1,
                'max_price' => 2,
            ]);
        }
    }
}
