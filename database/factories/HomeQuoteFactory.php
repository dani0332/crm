<?php

namespace Database\Factories;

use App\Models\HomeQuote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HomeQuote>
 */
class HomeQuoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'personal_quote_id' => null,
            'uuid' => Str::upper(Str::random(6)),
            'code' => 'HOM-'.Str::upper(Str::random(8)),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile_no' => $this->faker->numerify('05########'),
            'source' => 'IMCRM',
            'quote_status_id' => null,
            'advisor_id' => null,
            'renewal_batch_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
