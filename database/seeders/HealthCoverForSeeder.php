<?php

namespace Database\Seeders;

use App\Models\HealthCoverFor;
use App\Traits\SeedsIfMissing;
use Illuminate\Database\Seeder;

class HealthCoverForSeeder extends Seeder
{
    use SeedsIfMissing;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->disableHealthCoverFor();
        $this->createHealthCoverFor();
    }

    private function disableHealthCoverFor(): void
    {
        $codes = ['AN_INDIVIDUAL', 'MY_FAMILY'];
        HealthCoverFor::whereIn('code', $codes)->update(['is_active' => 0]);
    }

    private function createHealthCoverFor(): void
    {
        $now = now();
        HealthCoverFor::updateOrCreate(
            ['code' => 'MY_COMPANY'],
            [
                'code' => 'MY_COMPANY',
                'text' => 'Group of Employees',
                'is_active' => 1,
                'sort_order' => 3,
                'updated_at' => $now,
            ]
        );

        $this->seedUpsertIfMissing(HealthCoverFor::class, [
            [
                'code' => 'INDIVIDUAL_AND_FAMILIES',
                'text' => 'Individual & Families',
                'is_active' => 1,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'DOMESTIC_HELPER',
                'text' => 'Domestic Helper',
                'is_active' => 1,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
