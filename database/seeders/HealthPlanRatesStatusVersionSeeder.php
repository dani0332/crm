<?php

namespace Database\Seeders;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use App\Models\HealthRate;
use App\Models\HealthRateControl;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HealthPlanRatesStatusVersionSeeder extends Seeder
{
    public function run(): void
    {
        $healthPlans = HealthPlan::where('version', null)->get();

        foreach ($healthPlans as $healthPlan) {
            DB::transaction(function () use ($healthPlan) {
                // Update plan
                $healthPlan->version = 1.0;
                $healthPlan->status = HealthPlanRateSheetStatusEnum::ACTIVE->value;
                $healthPlan->save();

                // Create health rate control (sheet)
                $healthRateControl = $this->createHealthRateControl($healthPlan->id);

                // Update rate
                HealthRate::where('health_plan_id', $healthPlan->id)->update([
                    'health_rate_control_id' => $healthRateControl->id,
                    'version' => 1.0,
                    'status' => HealthPlanRateSheetStatusEnum::ACTIVE->value,
                ]);
            });

            echo $healthPlan->id;
            exit;
        }
    }

    private function createHealthRateControl(int $healthPlanId): HealthRateControl
    {
        // Get Admin user
        $adminRole = Role::where('name', 'Admin')->first();
        $adminUser = $adminRole->users()->first();

        return HealthRateControl::create([
            'health_plan_id' => $healthPlanId,
            'version' => 1.0,
            'status' => HealthPlanRateSheetStatusEnum::ACTIVE->value,
            'effective_from' => '2026-06-01',
            'effective_to' => '2999-12-31',
            'total_records' => 1,
            'created_by' => $adminUser->id,
        ]);
    }
}
