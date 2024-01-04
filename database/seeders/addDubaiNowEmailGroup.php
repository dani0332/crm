<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addDubaiNowEmailGroup extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dubaiNowEmailGroup = ApplicationStorage::where('key_name', ApplicationStorageEnums::DUBAI_NOW_CC_GROUP)->first();
        if ($dubaiNowEmailGroup == null) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::DUBAI_NOW_CC_GROUP,
                'value' => 'dubainowallocations@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
