<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DttOCBNewBusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Dtt Initial Email
        $ocbNewBusinessMultiplePlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::OCB_NEW_BUSINESS_MULTIPLE_PLANS)->first();
        if (! $ocbNewBusinessMultiplePlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::OCB_NEW_BUSINESS_MULTIPLE_PLANS,
                'value' => '551',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $ocbNewBusinessZeroPlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::OCB_NEW_BUSINESS_ZERO_PLAN)->first();
        if (! $ocbNewBusinessZeroPlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::OCB_NEW_BUSINESS_ZERO_PLAN,
                'value' => '552',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $ocbNewBusinessSinglePlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::OCB_NEW_BUSINESS_SINGLE_PLAN)->first();
        if (! $ocbNewBusinessSinglePlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::OCB_NEW_BUSINESS_SINGLE_PLAN,
                'value' => '632',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FOLLOWUP WITHOUT PLAN
        $twoDaysWithoutPlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_AFTER_TWO_DAYS_FOLLOWUP_WITHOUT_PLAN)->first();
        if (! $twoDaysWithoutPlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_AFTER_TWO_DAYS_FOLLOWUP_WITHOUT_PLAN,
                'value' => '544',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $sevenDaysWithoutPlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITHOUT_PLAN)->first();
        if (! $sevenDaysWithoutPlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITHOUT_PLAN,
                'value' => '545',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $thirteenDaysWithoutPlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITHOUT_PLAN)->first();
        if (! $thirteenDaysWithoutPlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITHOUT_PLAN,
                'value' => '546',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $twentyDaysWithoutPlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITHOUT_PLAN)->first();
        if (! $twentyDaysWithoutPlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITHOUT_PLAN,
                'value' => '547',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $twentyEightDaysWithoutPlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITHOUT_PLAN)->first();
        if (! $twentyEightDaysWithoutPlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITHOUT_PLAN,
                'value' => '548',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // FOLLOWUP WITH PLAN

        $twoDaysWitPlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_AFTER_TWO_DAYS_FOLLOWUP_WITH_PLAN)->first();
        if (! $twoDaysWitPlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_AFTER_TWO_DAYS_FOLLOWUP_WITH_PLAN,
                'value' => '536',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $sevenDaysWitPlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITH_PLAN)->first();
        if (! $sevenDaysWitPlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_AFTER_SEVEN_DAYS_FOLLOWUP_WITH_PLAN,
                'value' => '538',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $thirteenDaysWitPlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITH_PLAN)->first();
        if (! $thirteenDaysWitPlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_AFTER_THIRTEEN_DAYS_FOLLOWUP_WITH_PLAN,
                'value' => '539',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $twentyDaysWitPlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITH_PLAN)->first();
        if (! $twentyDaysWitPlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_AFTER_TWENTY_DAYS_FOLLOWUP_WITH_PLAN,
                'value' => '540',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $twentyEightDaysWitPlan = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITH_PLAN)->first();
        if (! $twentyEightDaysWitPlan) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_AFTER_TWENTYEIGHT_DAYS_FOLLOWUP_WITH_PLAN,
                'value' => '541',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
