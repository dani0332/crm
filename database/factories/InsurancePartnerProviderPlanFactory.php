<?php

namespace Database\Factories;

use App\Models\InsurancePartnerProvider;
use App\Models\InsurancePartnerProviderPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsurancePartnerProviderPlanFactory extends Factory
{
    protected $model = InsurancePartnerProviderPlan::class;

    public function definition(): array
    {
        return [
            'partner_provider_id' => InsurancePartnerProvider::factory(),
            'plan_id' => $this->faker->randomNumber(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
