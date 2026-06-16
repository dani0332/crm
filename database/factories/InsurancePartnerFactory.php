<?php

namespace Database\Factories;

use App\Models\InsurancePartner;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsurancePartnerFactory extends Factory
{
    protected $model = InsurancePartner::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('PARTNER_???'),
            'email' => $this->faker->unique()->safeEmail(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
