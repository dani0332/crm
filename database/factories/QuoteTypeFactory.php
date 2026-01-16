<?php

namespace Database\Factories;

use App\Models\QuoteType;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteTypeFactory extends Factory
{
    protected $model = QuoteType::class;

    public function definition()
    {
        return [
            'name' => 'Cyber', // simple type for testing
        ];
    }
}
