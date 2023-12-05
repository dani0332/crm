<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addDubaiNowLeadSourceExemptionInAppStorage extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dubaiNow = ApplicationStorage::where('key_name', ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION)->first();
        if ($dubaiNow == null) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION,
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
