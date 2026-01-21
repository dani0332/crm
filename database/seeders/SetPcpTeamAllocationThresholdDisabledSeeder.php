<?php

namespace Database\Seeders;

use App\Enums\TeamNameEnum;
use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SetPcpTeamAllocationThresholdDisabledSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Team::where('name', TeamNameEnum::PCP)
            ->where('allocation_threshold_enabled', true)
            ->update(['allocation_threshold_enabled' => false]);
    }
}
