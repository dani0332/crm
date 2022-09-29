<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SendPolicyApplicationStorageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sibCarTemplateId = ApplicationStorage::where('key_name', 'SIB_CAR_SEND_POLICY_TEMPLATE_ID')->first();
        if (! $sibCarTemplateId) {
            DB::table('application_storage')->insert([
                'key_name' => 'SIB_CAR_SEND_POLICY_TEMPLATE_ID',
                'value' => '389',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $dnircContactNumber = ApplicationStorage::where('key_name', 'DNIRC_CUSTOMER_SUPPORT_NUMBER')->first();
        if (! $dnircContactNumber) {
            DB::table('application_storage')->insert([
                'key_name' => 'DNIRC_CUSTOMER_SUPPORT_NUMBER',
                'value' => '800-4101',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $axaContactNumber = ApplicationStorage::where('key_name', 'AXA_CUSTOMER_SUPPORT_NUMBER')->first();
        if (! $axaContactNumber) {
            DB::table('application_storage')->insert([
                'key_name' => 'AXA_CUSTOMER_SUPPORT_NUMBER',
                'value' => '800 292',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $ntContactNumber = ApplicationStorage::where('key_name', 'NT_CUSTOMER_SUPPORT_NUMBER')->first();
        if (! $ntContactNumber) {
            DB::table('application_storage')->insert([
                'key_name' => 'NT_CUSTOMER_SUPPORT_NUMBER',
                'value' => '800 4101',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $oicContactNumber = ApplicationStorage::where('key_name', 'OIC_CUSTOMER_SUPPORT_NUMBER')->first();
        if (! $oicContactNumber) {
            DB::table('application_storage')->insert([
                'key_name' => 'OIC_CUSTOMER_SUPPORT_NUMBER',
                'value' => '800-6565',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $qicContactNumber = ApplicationStorage::where('key_name', 'QIC_CUSTOMER_SUPPORT_NUMBER')->first();
        if (! $qicContactNumber) {
            DB::table('application_storage')->insert([
                'key_name' => 'QIC_CUSTOMER_SUPPORT_NUMBER',
                'value' => '800 4900',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $rsaContactNumber = ApplicationStorage::where('key_name', 'RSA_CUSTOMER_SUPPORT_NUMBER')->first();
        if (! $rsaContactNumber) {
            DB::table('application_storage')->insert([
                'key_name' => 'RSA_CUSTOMER_SUPPORT_NUMBER',
                'value' => '800 462 372',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tmContactNumber = ApplicationStorage::where('key_name', 'TM_CUSTOMER_SUPPORT_NUMBER')->first();
        if (! $tmContactNumber) {
            DB::table('application_storage')->insert([
                'key_name' => 'TM_CUSTOMER_SUPPORT_NUMBER',
                'value' => '800 4900',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
