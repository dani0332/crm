<?php

namespace Database\Seeders;

use App\Models\BusinessInsuranceType;
use Illuminate\Database\Seeder;

class BusinessTypeInsuranceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $newBusinessInsuranceTypes = ['Money Insurance', 'Livestock Insurance', 'Marine Cargo - Open Cover', 'Marine Cargo (Individual Shipment) Insurance', 'Holiday Homes', 'Medical Malpractice', 'Fidelity Guarantee', 'Goods In Transit'];
        foreach ($newBusinessInsuranceTypes as $newType) {
            $sme = BusinessInsuranceType::updateOrCreate([
                'code' => $newType,
            ], [
                'text' => $newType,
                'is_active' => 1,
            ]);
        }
    }
}
