<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CustomerTypeEnum;
use App\Models\Insured;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Insured>
 */
class InsuredFactory extends Factory
{
    protected $model = Insured::class;

    public function definition(): array
    {
        return [
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'id_type' => 'passport',
            'id_number' => 'I-'.fake()->unique()->numerify('########'),
        ];
    }

    public function entity(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => CustomerTypeEnum::Entity,
            'first_name' => 'Acme',
            'last_name' => 'Corp',
            'id_number' => 'E-'.fake()->unique()->numerify('########'),
        ]);
    }
}
