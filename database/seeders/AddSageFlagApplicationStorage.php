<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ApplicationStorage;
use Illuminate\Support\Facades\DB;

class AddSageFlagApplicationStorage extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //SAGE_ENABLED FOR PAYMENTS
        $sageFlag = ApplicationStorage::where('key_name', 'SAGE_ENABLED')->get();
        if (count($sageFlag) == 0) {
            DB::table('application_storage')->insert([[
                'key_name' => 'SAGE_ENABLED',
                'value' => '1',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }
    }
}
