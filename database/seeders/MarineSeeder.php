<?php

namespace Database\Seeders;

use App\Enums\quoteBusinessTypeCode;
use App\Models\BusinessInsuranceType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MarineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $newBusinessInsuranceTypes = [quoteBusinessTypeCode::marineCargoIndividual];
        foreach ($newBusinessInsuranceTypes as $newType) {
            BusinessInsuranceType::updateOrCreate([
                'code' => 'Marine Cargo',
            ], [
                'code' => $newType,
                'text' => $newType,
                'is_active' => 1,
            ]);
        }
    }
}
