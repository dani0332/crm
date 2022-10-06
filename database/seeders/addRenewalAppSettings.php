<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addRenewalAppSettings extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ApplicationStorage::where('')
        ApplicationStorage::insert([
            [
                'key_name' => 'LEAD_ALLOCATION_START_TIME',
                'value' => '08:00:00',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key_name' => 'LEAD_ALLOCATION_STOP_TIME',
                'value' => '18:30:00',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key_name' => 'LEAD_ALLOCATION_IS_QUEUE_LIFO',
                'value' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
