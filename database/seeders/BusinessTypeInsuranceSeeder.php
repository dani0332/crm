<?php

namespace Database\Seeders;

use App\Enums\quoteBusinessTypeCode;
use App\Models\BusinessInsuranceType;
use Illuminate\Database\Seeder;

class BusinessTypeInsuranceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $newBusinessInsuranceTypes = [quoteBusinessTypeCode::moneyInsurance, quoteBusinessTypeCode::liveStock, quoteBusinessTypeCode::marineCargoOpenCover, quoteBusinessTypeCode::holidayHomes, quoteBusinessTypeCode::medicalMalpractices, quoteBusinessTypeCode::fidelityGuarantee, quoteBusinessTypeCode::goodsInTransit];
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
