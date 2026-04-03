<?php

namespace Database\Seeders;

use App\Enums\quoteTypeCode;
use App\Enums\TeamCategoryEnum;
use App\Enums\TeamNameEnum;
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
        $this->seedHealthTeamAUH();
        $this->updateNonAUHHealthTeamCategory();
    }

    private function seedHealthTeamPEC(): void
    {
        $healthTeam = Team::where('name', 'Health')->first();

        if ($healthTeam) {
            Team::firstOrCreate(
                [
                    'name' => TeamNameEnum::PEC,
                    'type' => TeamTypeEnum::TEAM,
                    'parent_team_id' => $healthTeam->id,
                ],
                [
                    'code' => 'PEC',
                    'is_active' => 1,
                    'category' => TeamCategoryEnum::NON_AUH,
                    'allocation_threshold_enabled' => true,
                    'min_price' => 1,
                    'max_price' => 2,
                ]
            );
        }
    }

    private function seedHealthTeamAUH(): void
    {
        $healthTeam = Team::where('name', 'Health')->first();

        if ($healthTeam) {
            Team::firstOrCreate(
                [
                    'name' => TeamNameEnum::AUH,
                    'type' => TeamTypeEnum::TEAM,
                    'parent_team_id' => $healthTeam->id,
                ],
                [
                    'code' => 'AUH',
                    'is_active' => 1,
                    'category' => TeamCategoryEnum::AUH,
                    'allocation_threshold_enabled' => true,
                    'min_price' => 0,
                    'max_price' => 2000,
                ]
            );
        }
    }

    private function updateNonAUHHealthTeamCategory(): void
    {
        // Update relevant teams category
        Team::whereIn('name', [quoteTypeCode::EBP, quoteTypeCode::RM_SPEED, quoteTypeCode::RM_NB, TeamNameEnum::GBP])
            ->where('is_active', 1)
            ->where('type', TeamTypeEnum::TEAM)->update([
                'category' => TeamCategoryEnum::NON_AUH,
            ]);
    }
}
