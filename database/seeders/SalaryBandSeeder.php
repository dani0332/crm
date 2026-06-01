<?php

namespace Database\Seeders;

use App\Models\SalaryBand;
use App\Traits\SeedsFirstOrCreateIfMissing;
use Illuminate\Database\Seeder;

class SalaryBandSeeder extends Seeder
{
    use SeedsFirstOrCreateIfMissing;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->disableSalaryBand();
        $this->createSalaryBand();
    }

    private function disableSalaryBand(): void
    {
        $codes = ['ABOVE_4000'];
        SalaryBand::whereIn('code', $codes)->update(['is_active' => 0]);
    }

    private function createSalaryBand(): void
    {
        $now = now();
        SalaryBand::updateOrCreate(
            ['code' => 'BELOW_OR_EQ_4000'],
            [
                'code' => 'BELOW_OR_EQ_4000',
                'text' => 'Less than or Equal to AED 4,000/month',
                'is_active' => 1,
            ]
        );

        $this->seedFirstOrCreateIfMissing(SalaryBand::class, [
            [
                'code' => 'BETWEEN_4001_AND_12000',
                'text' => 'AED 4,001 to AED 12,000/month',
                'is_active' => 1,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'ABOVE_12000',
                'text' => 'More than AED 12,000/month',
                'is_active' => 1,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'NO_SALARY_DEPENDENTS_OR_CHILDREN',
                'text' => 'No Salary (Dependents or Children)',
                'is_active' => 1,
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'NO_SALARY_COMMISSION_ONLY',
                'text' => 'No Salary, Commission Only',
                'is_active' => 1,
                'sort_order' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
