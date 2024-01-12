<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonalQuoteFactory extends Factory
{
    protected $model = PersonalQuote::class;
    public function definition()
    {
        $priceType = $this->faker->randomElement(['price_vat_applicable', 'price_vat_not_applicable']);
        $commissionType = $this->faker->randomElement(['commission_vat_applicable', 'commission_vat_not_applicable']);

        return [
            'uuid' => $this->faker->uuid,
            'advisor_id' => User::all()->random()->id,
            'quote_type_id' => QuoteType::all()->random()->id,
            'code' => $this->faker->randomNumber(),
            'policy_issuance_date' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'policy_number' => $this->faker->randomNumber(),
            'policy_expiry_date' => $this->faker->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'customer_id' => Customer::all()->random()->id,
            'discount_value' => $this->faker->optional()->randomFloat(2, 100, 500),
            'price_vat_applicable' => $priceType === 'price_vat_applicable' ? $this->faker->randomFloat(2, 1000, 10000) : 0,
            'price_vat_not_applicable' => $priceType === 'price_vat_not_applicable' ? $this->faker->randomFloat(2, 1000, 10000) : 0,
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'updated_at' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'commission_vat_applicable' => $commissionType === 'commission_vat_applicable' ? $this->faker->randomFloat(2, 1000, 10000) : 0,
            'commission_vat_not_applicable' => $commissionType === 'commission_vat_not_applicable' ? $this->faker->randomFloat(2, 1000, 10000) : 0,
        ];
    }
}
