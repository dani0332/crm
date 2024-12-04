<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class ApplicationStorageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ADVISOR_CONVERSION_QUOTE_STATUS_DATE],
            [
                'value' => '2024-12-01',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::ENABLE_PAYMENT_NOTIFICATION_EMAIL],
            [
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_ACCESS_KEY],
            [
                'value' => 'PFW43eLvGkOFh521QmolXW1fTLpT5C3Z3hiA',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::NB_MOTOR_FOLLOWUP_DELAY_DURATION],
            [
                'value' => '24',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::PROCESS_CC_PAYMENTS_ENABLED],
            [
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        $this->seedHomeAdvisors();
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::BIRD_WHATSAPP_NO_PLANS_ASSIGNMENT_WORKFLOW],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/5fd51eb0-a17a-43d4-b9a8-11910469e7ac/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::PUBLIC_HOLIDAY_START_DATE],
            [
                'value' => '2024-12-02 10:00:00',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::PUBLIC_HOLIDAY_END_DATE],
            [
                'value' => '2024-12-03 23:59:59',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

    }

    private function seedHomeAdvisors()
    {
        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_VALUE_ADVISORS],
            [
                'value' => 'marialuisa.deguzman@insurancemarket.ae,virgilio.ocon@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_VOLUME_ADVISORS],
            [
                'value' => 'ghana.naeem@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_OCB_AUTOMATED_FOLLOWUPS],
            [
                'value' => 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/114f64e5-5a67-4110-bb66-7038f3f34c04/invoke-sync',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );

        ApplicationStorage::firstOrCreate(
            ['key_name' => ApplicationStorageEnums::HOME_OCB_AUTOMATED_FOLLOWUPS_SWITCH],
            [
                'value' => '0',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ],
        );


    }
}
