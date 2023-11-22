<?php

namespace Database\Seeders;

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
        $enableCammyFollowUps = ApplicationStorage::where('key_name', 'ENABLE_CAMMY_FOLLOWUP')->first();
        if (! $enableCammyFollowUps) {
            ApplicationStorage::insert([
                'key_name' => 'ENABLE_CAMMY_FOLLOWUPS',
                'value' => '0',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
