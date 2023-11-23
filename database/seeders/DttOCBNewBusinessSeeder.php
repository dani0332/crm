<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DttOCBNewBusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ocbNewBusinessSingleMultiple = ApplicationStorage::where('key_name', 'OCB_NEW_BUSINESS_SINGLE_MULTIPLE_PLANS')->first();
        if (!$ocbNewBusinessSingleMultiple) {
            DB::table('application_storage')->insert([
                'key_name' => 'OCB_NEW_BUSINESS_SINGLE_MULTIPLE_PLANS',
                'value' => '551',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $ocbNewBusinessSingleMultiple = ApplicationStorage::where('key_name', 'OCB_NEW_BUSINESS_ZERO_PLANS')->first();
        if (!$ocbNewBusinessSingleMultiple) {
            DB::table('application_storage')->insert([
                'key_name' => 'OCB_NEW_BUSINESS_ZERO_PLANS',
                'value' => '552',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FOLLOWUP WITHOUT PLAN
        $DTT_AFTER_TWO_DAYS_FOLLOWUP_WITHOUT_PLAN = ApplicationStorage::where('key_name', 'DTT_AFTER_TWO_DAYS_FOLLOWUP_WITHOUT_PLAN')->first();
        if (!$DTT_AFTER_TWO_DAYS_FOLLOWUP_WITHOUT_PLAN) {
            DB::table('application_storage')->insert([
                'key_name' => 'DTT_AFTER_TWO_DAYS_FOLLOWUP_WITHOUT_PLAN',
                'value' => '544',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITHOUT_PLAN = ApplicationStorage::where('key_name', 'DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITHOUT_PLAN')->first();
        if (!$DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITHOUT_PLAN) {
            DB::table('application_storage')->insert([
                'key_name' => 'DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITHOUT_PLAN',
                'value' => '545',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITHOUT_PLAN = ApplicationStorage::where('key_name', 'DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITHOUT_PLAN')->first();
        if (!$DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITHOUT_PLAN) {
            DB::table('application_storage')->insert([
                'key_name' => 'DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITHOUT_PLAN',
                'value' => '546',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITHOUT_PLAN = ApplicationStorage::where('key_name', 'DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITHOUT_PLAN')->first();
        if (!$DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITHOUT_PLAN) {
            DB::table('application_storage')->insert([
                'key_name' => 'DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITHOUT_PLAN',
                'value' => '547',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITHOUT_PLAN = ApplicationStorage::where('key_name', 'DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITHOUT_PLAN')->first();
        if (!$DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITHOUT_PLAN) {
            DB::table('application_storage')->insert([
                'key_name' => 'DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITHOUT_PLAN',
                'value' => '548',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FOLLOWUP WITH PLAN

          $DTT_AFTER_TWO_DAYS_FOLLOWUP_WITH_PLAN = ApplicationStorage::where('key_name', 'DTT_AFTER_TWO_DAYS_FOLLOWUP_WITH_PLAN')->first();
        if (!$DTT_AFTER_TWO_DAYS_FOLLOWUP_WITH_PLAN) {
            DB::table('application_storage')->insert([
                'key_name' => 'DTT_AFTER_TWO_DAYS_FOLLOWUP_WITH_PLAN',
                'value' => '536',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITH_PLAN = ApplicationStorage::where('key_name', 'DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITH_PLAN')->first();
        if (!$DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITH_PLAN) {
            DB::table('application_storage')->insert([
                'key_name' => 'DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITH_PLAN',
                'value' => '538',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITH_PLAN = ApplicationStorage::where('key_name', 'DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITH_PLAN')->first();
        if (!$DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITH_PLAN) {
            DB::table('application_storage')->insert([
                'key_name' => 'DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITH_PLAN',
                'value' => '539',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITH_PLAN = ApplicationStorage::where('key_name', 'DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITH_PLAN')->first();
        if (!$DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITH_PLAN) {
            DB::table('application_storage')->insert([
                'key_name' => 'DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITH_PLAN',
                'value' => '540',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITH_PLAN = ApplicationStorage::where('key_name', 'DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITH_PLAN')->first();
        if (!$DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITH_PLAN) {
            DB::table('application_storage')->insert([
                'key_name' => 'DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITH_PLAN',
                'value' => '541',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
