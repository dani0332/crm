<?php

namespace Database\Seeders;

use App\Enums\TeamNameEnum;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

class SetPcpTeamAllocationThresholdDisabledSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        try {
            Team::where('name', TeamNameEnum::PCP)
                ->where('allocation_threshold_enabled', true)
                ->update(['allocation_threshold_enabled' => false]);

            Team::where('name', TeamNameEnum::GBP)
                ->where('allocation_threshold_enabled', false)
                ->update(['allocation_threshold_enabled' => true]);
            DB::commit();

        } catch (Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }
    }
}
