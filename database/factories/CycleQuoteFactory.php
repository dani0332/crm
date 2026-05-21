<?php

namespace Database\Factories;

use App\Models\CycleQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CycleQuote>
 */
class CycleQuoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'personal_quote_id' => null,
            'cycle_make' => $this->faker->word(),
            'cycle_model' => $this->faker->word(),
            'year_of_manufacture_id' => null,
            'accessories' => null,
            'has_accident' => false,
            'has_good_condition' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
