<?php

namespace Database\Seeders;

use App\Models\VisaCategory;
use App\Traits\SeedsIfMissing;
use Illuminate\Database\Seeder;

class VisaCategorySeeder extends Seeder
{
    use SeedsIfMissing;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $this->seedUpsertIfMissing(VisaCategory::class, [
            [
                'code' => 'GOLDEN_VISA',
                'text' => 'Golden Visa',
                'is_active' => 1,
                'sort_order' => 1,
                'health_cover_for_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'INVESTOR_PARTNER',
                'text' => 'Investor / Partner',
                'is_active' => 1,
                'sort_order' => 2,
                'health_cover_for_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SELF_EMPLOYED_FREELANCE',
                'text' => 'Self Employed / Freelancer',
                'is_active' => 1,
                'sort_order' => 3,
                'health_cover_for_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SPONSORED_EMPLOYER_FAMILY',
                'text' => 'Sponsored (Employer or Family)',
                'is_active' => 0,
                'sort_order' => 4,
                'health_cover_for_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'NEWBORN_BORN_IN_UAE',
                'text' => 'Newborn (Born in UAE)',
                'is_active' => 1,
                'sort_order' => 6,
                'health_cover_for_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'DOMESTIC_WORKER_VISA_FOR_UAE_NATIONALS',
                'text' => 'Domestic Worker Visa for UAE Nationals',
                'is_active' => 1,
                'sort_order' => 6,
                'health_cover_for_id' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'DOMESTIC_WORKER_VISA_FOR_NON_UAE_NATIONALS',
                'text' => 'Domestic Worker Visa for Non-UAE Nationals',
                'is_active' => 1,
                'sort_order' => 7,
                'health_cover_for_id' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'DEPENDENT_FAMILY',
                'text' => 'Dependent / Family',
                'is_active' => 1,
                'sort_order' => 4,
                'health_cover_for_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'EMPLOYMENT',
                'text' => 'Employment',
                'is_active' => 1,
                'sort_order' => 5,
                'health_cover_for_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'STUDENT',
                'text' => 'Student',
                'is_active' => 1,
                'sort_order' => 7,
                'health_cover_for_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'RETIREE',
                'text' => 'Retiree',
                'is_active' => 1,
                'sort_order' => 8,
                'health_cover_for_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'UAE_Citizen',
                'text' => 'UAE Citizen',
                'is_active' => 1,
                'sort_order' => 12,
                'health_cover_for_id' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
