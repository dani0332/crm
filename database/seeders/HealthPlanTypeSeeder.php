<?php

namespace Database\Seeders;

use App\Models\HealthPlanType;
use Illuminate\Database\Seeder;

class HealthPlanTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $healthPlanType = HealthPlanType::all()->count();
        if ($healthPlanType == 0) {
            HealthPlanType::create([
                'code' => 'BASIC',
                'text' => 'Basic',
                'text_ar' => 'Basic',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            HealthPlanType::create([
                'code' => 'GOOD',
                'text' => 'Good',
                'text_ar' => 'Good',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            HealthPlanType::create([
                'code' => 'BEST',
                'text' => 'Best',
                'text_ar' => 'Best',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
