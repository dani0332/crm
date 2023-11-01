<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DttOCBNewBusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ocbNewBusinessSingleMultiple = ApplicationStorage::where('key_name', 'OCB_NEW_BUSINESS_SINGLE_MULTIPLE_PLANS')->first();
        if (!$ocbNewBusinessSingleMultiple) {
            DB::table('application_storage')->insert([
                'key_name' => 'OCB_NEW_BUSINESS_SINGLE_MULTIPLE_PLANS',
                'value' => '493',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $ocbNewBusinessSingleMultiple = ApplicationStorage::where('key_name', 'OCB_NEW_BUSINESS_ZERO_PLANS')->first();
        if (!$ocbNewBusinessSingleMultiple) {
            DB::table('application_storage')->insert([
                'key_name' => 'OCB_NEW_BUSINESS_ZERO_PLANS',
                'value' => '494',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
