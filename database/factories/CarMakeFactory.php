<?php

namespace Database\Factories;

use App\Models\CarMake;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarMakeFactory extends Factory
{
    protected $model = CarMake::class;

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (CarMake $carMake) {
            // Use SQLite connection for tests
            if (app()->environment('testing')) {
                $carMake->setConnection('sqlite');
            }
        });
    }

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('???'),
            'text' => $this->faker->company().' Motors',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
