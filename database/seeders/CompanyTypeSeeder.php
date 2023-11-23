<?php

namespace Database\Seeders;

use App\Models\Lookup;
use Illuminate\Database\Seeder;

class CompanyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Lookup::updateOrCreate(['key' => 'company-type', 'code' => 'Consultancy'], ['text' => 'Consultancy']);
        Lookup::updateOrCreate(['key' => 'company-type', 'code' => 'Manufacturing'], ['text' => 'Manufacturing']);
        Lookup::updateOrCreate(['key' => 'company-type', 'code' => 'LegalServices'], ['text' => 'Legal Services']);
        Lookup::updateOrCreate(['key' => 'company-type', 'code' => 'Brokers'], ['text' => 'Brokers']);
        Lookup::updateOrCreate(['key' => 'company-type', 'code' => 'Construction'], ['text' => 'Construction']);
        Lookup::updateOrCreate(['key' => 'company-type', 'code' => 'FoodBeverages'], ['text' => 'Food & Beverages']);
        Lookup::updateOrCreate(['key' => 'company-type', 'code' => 'ServiceProvider'], ['text' => 'Service Provider']);
    }
}
