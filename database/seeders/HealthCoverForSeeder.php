<?php

namespace Database\Seeders;

use App\Models\HealthCoverFor;
use Illuminate\Database\Seeder;

class HealthCoverForSeeder extends Seeder
{
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
        $healthCoverForUpdates = [
            [
                'code' => 'MY_COMPANY',
                'text' => 'Group of Employees',
                'is_active' => 1,
                'sort_order' => 3,
                'updated_at' => now(),
            ],
        ];

        foreach ($healthCoverForUpdates as $healthCoverForItem) {
            HealthCoverFor::updateOrCreate(
                ['code' => $healthCoverForItem['code']],
                $healthCoverForItem
            );
        }

        $healthCoverFor = [
            [
                'code' => 'INDIVIDUAL_AND_FAMILIES',
                'text' => 'Individual & Families',
                'is_active' => 1,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'DOMESTIC_HELPER',
                'text' => 'Domestic Helper',
                'is_active' => 1,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($healthCoverFor as $healthCoverForItem) {
            HealthCoverFor::firstOrCreate(
                ['code' => $healthCoverForItem['code']],
                $healthCoverForItem
            );
        }
    }
}
