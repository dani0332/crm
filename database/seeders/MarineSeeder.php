<?php

namespace Database\Seeders;

use App\Enums\quoteBusinessTypeCode;
use App\Models\BusinessInsuranceType;
use Illuminate\Database\Seeder;

class MarineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        BusinessInsuranceType::updateOrCreate([
            'code' => 'Marine Cargo',
        ], [
            'code' => quoteBusinessTypeCode::marineCargoIndividual,
            'text' => quoteBusinessTypeCode::marineCargoIndividual,
            'is_active' => 1,
        ]);
    }
}
