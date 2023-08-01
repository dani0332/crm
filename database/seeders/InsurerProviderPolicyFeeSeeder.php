<?php

namespace Database\Seeders;

use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;

class InsurerProviderPolicyFeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Sukoon 50 AED
        $sukoonPolicy = InsuranceProvider::where('code', 'OIC')->first();
        if( empty($sukoonPolicy->health_policy_fee) )
            $sukoonPolicy->update(['health_policy_fee' => 50]);

        // Orient 25 AED
        $orientPolicy = InsuranceProvider::where('code', 'OI')->first();
        if( empty($orientPolicy->health_policy_fee) )
            $orientPolicy->update(['health_policy_fee' => 25]);


    }
}
