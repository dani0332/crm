<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class CarLostStorageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(!ApplicationStorage::where('key_name', ApplicationStorageEnums::CAR_SOLD_STATUS_REJECTION_TEMPLATE)->first()) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::CAR_SOLD_STATUS_REJECTION_TEMPLATE,
                'value' => 472,
                'is_active' => 1,
            ]);
        }

        if(!ApplicationStorage::where('key_name', ApplicationStorageEnums::UNCONTACTABLE_STATUS_REJECTION_TEMPLATE)->first()) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::UNCONTACTABLE_STATUS_REJECTION_TEMPLATE,
                'value' => 473,
                'is_active' => 1,
            ]);
        }
    }
}
