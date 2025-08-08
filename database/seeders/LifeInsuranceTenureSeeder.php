<?php

namespace Database\Seeders;

use App\Models\LifeInsuranceTenure;
use Illuminate\Database\Seeder;

class LifeInsuranceTenureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // MARK SOME RECORDS AS DELETED
        LifeInsuranceTenure::whereIn('code', ['Term Insurance', 'Whole of Life Insurance', 'ENDOWMENT'])
            ->update(['is_deleted' => 1, 'is_active' => 0]);

        // INSERT NEW RECORD
        LifeInsuranceTenure::firstOrCreate(
            ['code' => 'Life'],
            [
                'text' => 'Life',
                'text_ar' => 'حياة',
                'is_active' => 1,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

}
