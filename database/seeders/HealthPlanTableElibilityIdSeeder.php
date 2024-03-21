<?php

namespace Database\Seeders;

use App\Models\HealthPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HealthPlanTableElibilityIdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('health_rates')
            ->select('health_plan_id', 'health_rating_eligibility_id')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->groupBy('health_plan_id')
            ->orderBy('health_plan_id')
            ->chunk(500, function ($healthRates) {
                foreach ($healthRates as $healthRate) {

                    $healthPlan = HealthPlan::whereNull('health_rating_eligibility_id')
                        ->where('id', $healthRate->health_plan_id)
                        ->first();

                    if ($healthPlan && $healthPlan->health_rating_eligibility_id == null) {
                        $healthPlan->health_rating_eligibility_id = $healthRate->health_rating_eligibility_id;
                        $healthPlan->save();
                    }
                }
            });
    }
}
