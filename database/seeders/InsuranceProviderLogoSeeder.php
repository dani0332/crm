<?php

namespace Database\Seeders;

use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;

class InsuranceProviderLogoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $insurance_providers = InsuranceProvider::all();
        if (isset($insurance_providers)) {
            foreach ($insurance_providers as $key => $insurance_provider) {
                $insurance_provider->logo_url = url('/insurance_providers/'.strtolower($insurance_provider->code).'.png');
                $insurance_provider->save();
            }
        }
    }
}
