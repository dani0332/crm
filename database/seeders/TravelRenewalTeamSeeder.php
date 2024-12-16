<?php

namespace Database\Seeders;

use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\Team;
use Illuminate\Database\Seeder;

class TravelRenewalTeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Team::firstOrCreate(
            ['name' => TeamNameEnum::TRAVEL_RENEWALS],
            [
                'type' => TeamTypeEnum::TEAM,
                'is_active' => 1,
                'slabs_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),

            ],
        );
    }
}
