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
    /**
     * Idempotent: only targets plans where `version` is still NULL. Each
     * branch sets `version` to 1.0 once processed, so already-migrated plans
     * are excluded from subsequent runs. The rates branch also wraps the
     * version bump, HealthRateControl creation, and rate update in a single
     * DB::transaction() — if any step fails, the version save rolls back too,
     * leaving the plan NULL so the next run retries it cleanly instead of
     * leaving it half-migrated.
     */
    public function run(): void
    {
        // Fetch all existing plans (without version)
        $healthPlans = HealthPlan::whereNull('version')->get();

        foreach ($healthPlans as $healthPlan) {
            // If no rates
            if ($healthPlan->rates->count() == 0) {
                // Just update plan and skip iteration
                $healthPlan->version = 1.0;
                $healthPlan->status = HealthPlanRateSheetStatusEnum::DRAFT->value;
                $healthPlan->save();

                continue;
            }

            DB::transaction(function () use ($healthPlan) {
                // Update plan
                $healthPlan->version = 1.0;
                $healthPlan->status = HealthPlanRateSheetStatusEnum::ACTIVE->value;
                $healthPlan->gender_enabled = true;
                $healthPlan->marital_status_enabled = true;
                $healthPlan->save();

                // Create health rate control (sheet)
                $healthRateControl = $this->createHealthRateControl($healthPlan->id, $healthPlan->rates->count());

                // Update rate
                HealthRate::where('health_plan_id', $healthPlan->id)->update([
                    'health_rate_control_id' => $healthRateControl->id,
                    'version' => 1.0,
                    'status' => HealthPlanRateSheetStatusEnum::ACTIVE->value,
                ]);
            });
        }
    }

    private function createHealthRateControl(int $healthPlanId, int $totalRecords): HealthRateControl
    {
        // Get Admin user
        $adminRole = Role::where('name', 'Admin')->first();
        $adminUser = $adminRole->users()->first();

        return HealthRateControl::create([
            'health_plan_id' => $healthPlanId,
            'version' => 1.0,
            'status' => HealthPlanRateSheetStatusEnum::ACTIVE->value,
            'effective_from' => '2026-06-04',
            'effective_to' => '2999-12-31',
            'total_records' => $totalRecords,
            'created_by' => $adminUser->id,
        ]);
    }
}
