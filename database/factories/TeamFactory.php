<?php

namespace Database\Factories;

use App\Enums\TeamCategoryEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }

    public function createForSqlite(): Team
    {
        return Team::forceCreate([
            'name' => TeamNameEnum::GBP,
            'code' => TeamNameEnum::GBP,
            'type' => TeamTypeEnum::TEAM,
            'is_active' => 1,
            'parent_team_id' => null,
            'category' => TeamCategoryEnum::NON_AUH->value,
            'allocation_threshold_enabled' => true,
            'min_price' => 1000,
            'max_price' => 1000000,
        ]);
    }
}
