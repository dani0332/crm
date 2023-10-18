<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addQuoteAllocationSwitch extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $allocationSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::QUOTE_ALLOCATION_SWITCH)->first();
        if ($allocationSwitch == null) {
            ApplicationStorage::insert([
                [
                    'key_name' => ApplicationStorageEnums::QUOTE_ALLOCATION_SWITCH,
                    'value' => 1,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
