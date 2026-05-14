<?php

namespace Database\Factories;

use App\Enums\QuoteTypes;
use App\Models\QuoteType;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteTypeFactory extends Factory
{
    protected $model = QuoteType::class;

    public function definition(): array
    {
        return [
            'text' => 'Cyber', // simple type for testing
        ];
    }

    public function createForSqlite(array $attributes = []): QuoteType
    {
        return QuoteType::unguarded(function () use ($attributes): QuoteType {
            return QuoteType::query()->create(array_merge([
                'code' => 'cyber',
                'text' => 'Cyber',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], $attributes));
        });
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
