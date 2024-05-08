<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
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
        $data = [
            'SIB_CAR_SEND_POLICY_TEMPLATE_ID' => '389',
            'DNIRC_CUSTOMER_SUPPORT_NUMBER' => '800-4101',
            'AXA_CUSTOMER_SUPPORT_NUMBER' => '800 292',
            'NT_CUSTOMER_SUPPORT_NUMBER' => '800 4101',
            'OIC_CUSTOMER_SUPPORT_NUMBER' => '800-6565',
            'QIC_CUSTOMER_SUPPORT_NUMBER' => '800 4900',
            'RSA_CUSTOMER_SUPPORT_NUMBER' => '800 462 372',
            'TM_CUSTOMER_SUPPORT_NUMBER' => '800 4900',
            ApplicationStorageEnums::CAR_BOOK_POLICY_TEMPLATE => '591',
            ApplicationStorageEnums::TRAVEL_BOOK_POLICY_TEMPLATE => '612',
            ApplicationStorageEnums::HEALTH_BOOK_POLICY_TEMPLATE => '593',
            ApplicationStorageEnums::LIFE_BOOK_POLICY_TEMPLATE => '617',
            ApplicationStorageEnums::HOME_BOOK_POLICY_TEMPLATE => '616',
            ApplicationStorageEnums::PET_BOOK_POLICY_TEMPLATE => '615',
            ApplicationStorageEnums::BIKE_BOOK_POLICY_TEMPLATE => '592',
            ApplicationStorageEnums::CYCLE_BOOK_POLICY_TEMPLATE => '618',
            ApplicationStorageEnums::YACHT_BOOK_POLICY_TEMPLATE => '622',
            ApplicationStorageEnums::GROUP_MEDICAL_BOOK_POLICY_TEMPLATE => '613',
            ApplicationStorageEnums::CORPLINE_BOOK_POLICY_TEMPLATE => '614',
        ];

        foreach ($data as $key => $value) {
            $record = ApplicationStorage::where('key_name', $key)->first();
            if (! $record) {
                DB::table('application_storage')->insert([
                    'key_name' => $key,
                    'value' => $value,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $bikeBookPolicy = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIKE_BOOK_POLICY_TEMPLATE)->first();
        if (! $bikeBookPolicy) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::BIKE_BOOK_POLICY_TEMPLATE,
                'value' => '592',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $cycleBookPolicy = ApplicationStorage::where('key_name', ApplicationStorageEnums::CYCLE_BOOK_POLICY_TEMPLATE)->first();
        if (! $cycleBookPolicy) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::CYCLE_BOOK_POLICY_TEMPLATE,
                'value' => '618',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $yachtBookPolicy = ApplicationStorage::where('key_name', ApplicationStorageEnums::YACHT_BOOK_POLICY_TEMPLATE)->first();
        if (! $yachtBookPolicy) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::YACHT_BOOK_POLICY_TEMPLATE,
                'value' => '618',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
