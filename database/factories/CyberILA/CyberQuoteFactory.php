<?php

namespace Database\Factories;

use App\Models\CyberQuote;
use App\Models\PersonalQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CyberQuoteFactory extends Factory
{
    protected $model = CyberQuote::class;

    public function definition(): array
    {
        // First create a PersonalQuote
        $personalQuote = PersonalQuote::factory()->create([
            'quote_type_id' => 119, // Cyber quote type ID
        ]);

        return [
            'personal_quote_id' => $personalQuote->id,
            'emirate_of_registration_id' => 1,
            'coverage_id' => null,
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
