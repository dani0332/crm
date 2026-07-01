<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TravelQuote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TravelQuote>
 */
class TravelQuoteFactory extends Factory
{
    protected $model = TravelQuote::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'code' => 'TRV-'.$this->faker->unique()->numerify('######'),
            'quote_status_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
