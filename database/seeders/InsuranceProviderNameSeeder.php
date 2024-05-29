<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;

class InsuranceProviderNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $insuranceProvider = InsuranceProvider::firstOrCreate([
            'code' => 'AIG',
            'text' => 'American Home Assurance Company',
            'is_active' => true,
        ]);

        $insuranceProvider->quoteTypes()->syncWithoutDetaching([QuoteTypeId::Business]);

        $insuranceProvider = InsuranceProvider::firstOrCreate([
            'code' => 'AFNIC',
            'text' => 'Al Fujairah National Insurance Company',
            'is_active' => true,
        ]);

        $insuranceProvider->quoteTypes()->syncWithoutDetaching([QuoteTypeId::Business]);

        $insuranceProvider = InsuranceProvider::firstOrCreate([
            'code' => 'ASNIC',
            'text' => 'Al Sagr Insurance',
            'is_active' => true,
        ]);

        $insuranceProvider->quoteTypes()->syncWithoutDetaching([QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Bike, QuoteTypeId::Travel,
            QuoteTypeId::GroupMedical]);

        $insuranceProvider = InsuranceProvider::firstOrCreate([
            'code' => 'ALJALIL',
            'text' => 'Al Wathba National Insurance',
            'is_active' => true,
        ]);

        $insuranceProvider->quoteTypes()->syncWithoutDetaching([QuoteTypeId::Business, QuoteTypeId::Travel]);

        $insuranceProvider = InsuranceProvider::firstOrCreate([
            'code' => 'FPIL',
            'text' => 'Friends Provident International Limited',
            'is_active' => true,
        ]);

        $insuranceProvider->quoteTypes()->syncWithoutDetaching([QuoteTypeId::Life]);

        $insuranceProvider = InsuranceProvider::firstOrCreate([
            'code' => 'ST',
            'text' => 'Sukoon Takaful',
            'is_active' => true,
        ]);

        $insuranceProvider->quoteTypes()->syncWithoutDetaching([QuoteTypeId::Yacht]);

        $insuranceProvider = InsuranceProvider::firstOrCreate([
            'code' => 'ZILL',
            'text' => 'Zurich International Life Limited',
            'is_active' => true,
        ]);

        $insuranceProvider->quoteTypes()->syncWithoutDetaching([QuoteTypeId::Life]);
    }
}
