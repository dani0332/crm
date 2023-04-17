<?php

namespace Database\Seeders;

use App\Models\HealthFacilityCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HealthFacilityCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $healthFacilityCategory = HealthFacilityCategory::all()->count();
        if ($healthFacilityCategory == 0) {
            DB::table('health_facility_category')->insert([[
                'code' => 'CLI',
                'text' => 'Clinic',
                'text_ar' => 'Clinic',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'code' => 'HOS',
                'text' => 'Hospital',
                'text_ar' => 'Hospital',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'code' => 'DEN',
                'text' => 'Dental',
                'text_ar' => 'Dental',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'code' => 'OPT',
                'text' => 'Optical',
                'text_ar' => 'Optical',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'code' => 'DAS',
                'text' => 'Day Surgery',
                'text_ar' => 'Day Surgery',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'code' => 'DIA',
                'text' => 'Diagnostics',
                'text_ar' => 'Diagnostics',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'code' => 'PHA',
                'text' => 'Pharmacy',
                'text_ar' => 'Pharmacy',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }
    }
}
