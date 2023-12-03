<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addEnableLeadReassignment extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $enableReassignment = ApplicationStorage::where('key_name', ApplicationStorageEnums::ENABLE_LEAD_REASSIGNMENT)->first();
        if ($enableReassignment == null) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::ENABLE_LEAD_REASSIGNMENT,
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
