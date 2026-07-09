<?php

namespace Database\Seeders;

use App\Models\VisaCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Reverses all database changes made by the health pricing engine seeders:
 *   - VisaCategorySeeder (PRs #12757, #12764)
 *   - SplitHealthRatesGenderMaritalStatusSeeder
 *   - MakeGenderMaritalStatusUppercase
 *   - HealthPlanRatesStatusVersionSeeder
 */
class ReverseHealthPricingEngineSeeder extends Seeder
{
    public function run(): void
    {
        $this->reverseVisaCategories();
        $this->reverseHealthRatesGenderMaritalStatus();
        $this->reverseHealthPlanVersioning();
    }

    private function reverseVisaCategories(): void
    {
        // Remove visa categories added by the pricing engine (health_cover_for_id = 4)
        VisaCategory::whereIn('code', [
            'DEPENDENT_FAMILY',
            'EMPLOYMENT',
            'STUDENT',
            'RETIREE',
            'UAE_Citizen',
        ])->delete();

        // Restore SPONSORED_EMPLOYER_FAMILY to active (pricing engine deactivated it)
        VisaCategory::where('code', 'SPONSORED_EMPLOYER_FAMILY')
            ->update(['is_active' => 1]);

        // Restore NEWBORN_BORN_IN_UAE sort_order (pricing engine changed 5 → 6)
        VisaCategory::where('code', 'NEWBORN_BORN_IN_UAE')
            ->update(['sort_order' => 5]);
    }

    private function reverseHealthRatesGenderMaritalStatus(): void
    {
        // Reverses SplitHealthRatesGenderMaritalStatusSeeder + MakeGenderMaritalStatusUppercase.
        // Converts MALE/FEMALE + separate marital_status back to the legacy combined codes (M, FS, FM).
        DB::statement("
            UPDATE health_rates
            SET
                gender = CASE
                    WHEN gender = 'MALE' THEN 'M'
                    WHEN gender = 'FEMALE' AND marital_status = 'SINGLE' THEN 'FS'
                    WHEN gender = 'FEMALE' AND marital_status = 'MARRIED' THEN 'FM'
                    ELSE gender
                END,
                marital_status = NULL
            WHERE gender IN ('MALE', 'FEMALE')
        ");
    }

    private function reverseHealthPlanVersioning(): void
    {
        // Reverses HealthPlanRatesStatusVersionSeeder.
        // Deletes HealthRateControl records created by the seeder and resets the
        // version, status, gender_enabled, and marital_status_enabled columns on
        // health_plans and health_rates back to their pre-pricing-engine state.
        DB::transaction(function (): void {
            $seederControlIds = DB::table('health_rate_controls')
                ->where('effective_from', '2026-06-04')
                ->where('effective_to', '2999-12-31')
                ->where('version', 1.0)
                ->pluck('id');

            if ($seederControlIds->isNotEmpty()) {
                DB::table('health_rates')
                    ->whereIn('health_rate_control_id', $seederControlIds)
                    ->update([
                        'health_rate_control_id' => null,
                        'version' => null,
                        'status' => null,
                        'updated_at' => now(),
                    ]);

                DB::table('health_rate_controls')
                    ->whereIn('id', $seederControlIds)
                    ->delete();
            }

            DB::table('health_plans')
                ->where('version', 1.0)
                ->update([
                    'version' => null,
                    'status' => null,
                    'gender_enabled' => false,
                    'marital_status_enabled' => false,
                    'updated_at' => now(),
                ]);
        });
    }
}
