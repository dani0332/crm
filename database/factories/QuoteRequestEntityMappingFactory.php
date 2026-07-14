<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuoteRequestEntityMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteRequestEntityMappingFactory extends Factory
{
    protected $model = QuoteRequestEntityMapping::class;

    public function definition(): array
    {
        return [
            'quote_type_id' => $this->faker->randomNumber(2),
            'quote_request_id' => $this->faker->randomNumber(5),
            'entity_id' => $this->faker->randomNumber(5),
            'entity_type_code' => $this->faker->randomElement(['LLC', 'SOC', 'IND']),
        ];
    }
}
