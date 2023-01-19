<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addReportsMaxDays extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $maxDaysEntry = ApplicationStorage::where('key_name', 'MAX_DAYS_CAR_REPORTS')->count();
        if ($maxDaysEntry == 0) {
            ApplicationStorage::insert([
                'key_name' => 'MAX_DAYS_CAR_REPORTS',
                'value' => 92,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
