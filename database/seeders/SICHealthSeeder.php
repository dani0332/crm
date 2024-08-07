<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SICHealthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $sicHealthFollowup = ApplicationStorage::where('key_name', 'SIC_HEALTH_FOLLOWUP_TEMPLATE')->first();
        if (empty($sicHealthFollowup)) {
            info('SIC_HEALTH_FOLLOWUP_TEMPLATE started');

            //SIC_HEALTH_FOLLOWUP_TEMPLATE
            DB::table('application_storage')->insert([[
                'key_name' => 'SIC_HEALTH_FOLLOWUP_TEMPLATE',
                'value' => '0',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
            info('SIC_HEALTH_FOLLOWUP_TEMPLATE added');
        } else {
            info('SIC_HEALTH_FOLLOWUP_TEMPLATE already exists');
        }

        $sicHealthnoAdvisorFollowup = ApplicationStorage::where('key_name', 'SIC_HEALTH_NO_ADVISOR_TEMPLATE')->first();
        if (empty($sicHealthnoAdvisorFollowup)) {
            info('SIC_HEALTH_NO_ADVISOR_TEMPLATE started');

            //SIC_HEALTH_NO_ADVISOR_TEMPLATE
            DB::table('application_storage')->insert([[
                'key_name' => 'SIC_HEALTH_NO_ADVISOR_TEMPLATE',
                'value' => '0',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
            info('SIC_HEALTH_NO_ADVISOR_TEMPLATE added');
        } else {
            info('SIC_HEALTH_NO_ADVISOR_TEMPLATE already exists');
        }

    }
}
