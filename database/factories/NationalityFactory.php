<?php

namespace Database\Factories;

use App\Models\Nationality;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NationalityFactory extends Factory
{
    protected $model = Nationality::class;

    public function definition(): array
    {
        $country = $this->faker->unique()->country;

        return [
            'text' => $country,
            'code' => Str::upper(substr($country, 0, 3)),
            'is_active' => true,
        ];
    }
}

