<?php

namespace Database\Factories;

use App\Enums\QuoteTypes;
use App\Models\QuoteType;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteTypeFactory extends Factory
{
    protected $model = QuoteType::class;

    public function definition()
    {
        return [
            'text' => 'Cyber', // simple type for testing
        ];
    }

    public function createHealthForSqlite(): QuoteType
    {
        return QuoteType::forceCreate([
            'id' => QuoteTypes::HEALTH->id(),
            'code' => QuoteTypes::HEALTH->value,
            'text' => QuoteTypes::HEALTH->value,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
