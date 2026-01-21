<?php

namespace Database\Factories;

use App\Models\CyberQuote;
use App\Models\Emirate;
use App\Models\PersonalQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CyberQuoteFactory extends Factory
{
    protected $model = CyberQuote::class;

    public function definition(): array
    {
        return [
            'personal_quote_id' => PersonalQuote::factory([
                'uuid' => 'cyber-quote-'.uniqid(),
                'code' => 'CYB-'.uniqid(),
                'quote_type_id' => 19, // Cyber quote type ID (QuoteTypeId::Cyber)
            ]),
            'emirate_of_registration_id' => Emirate::factory(),
            'coverage_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * For testing with SQLite in-memory database.
     */
    public function forTest(): self
    {
        return $this->state(function (array $attributes) {
            // RefreshDatabase handles this now
            return $attributes;
        });
    }

    /**
     * Create without associated personal quote.
     */
    public function withoutPersonalQuote(): self
    {
        return $this->state(function (array $attributes) {
            $attributes['personal_quote_id'] = null;

            return $attributes;
        });
    }
}
