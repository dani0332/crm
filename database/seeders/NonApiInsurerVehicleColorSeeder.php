<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Lookup;
use App\Enums\LookupsEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Models\InsuranceProvider;

class NonApiInsurerVehicleColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $GIGInsurerProvider = InsuranceProvider::where('code', InsuranceProvidersEnum::AXA)->first();
        $GIGInsurerVehicleColors = Lookup::where('insurance_provider_id', $GIGInsurerProvider->id)
            ->where('key', LookupsEnum::VEHICLE_COLOR)->count();
    }
}
