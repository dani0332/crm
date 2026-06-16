<?php

namespace Database\Factories;

use App\Enums\QuoteTypes;
use App\Models\InsurancePartner;
use App\Models\InsurancePartnerProvider;
use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsurancePartnerProviderFactory extends Factory
{
    protected $model = InsurancePartnerProvider::class;

    public function definition(): array
    {
        return [
            'partner_id' => InsurancePartner::factory(),
            'provider_id' => InsuranceProvider::factory(),
            'quote_type_id' => QuoteTypes::CAR->id(),
            'auto_issuance_enabled' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
