<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addHealthManualAssignmentByPassUsers extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $byPassEmails = ApplicationStorage::where('key_name', ApplicationStorageEnums::HEALTH_MANUAL_ASSIGNMENT_USER_BYPASS)->first();
        if ($byPassEmails == null) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::HEALTH_MANUAL_ASSIGNMENT_USER_BYPASS,
                'value' => 'LostLeadsRM@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

    }
}
