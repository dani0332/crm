<?php

namespace Database\Factories;

use App\Models\GenericDocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class GenericDocumentTypeFactory extends Factory
{
    protected $model = GenericDocumentType::class;

    public function definition()
    {
        return [
            'code' => $this->faker->unique()->lexify('????_????'),
            'text' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
