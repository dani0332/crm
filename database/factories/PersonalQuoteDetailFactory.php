<?php

namespace Database\Factories;

use App\Models\PersonalQuoteDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonalQuoteDetail>
 */
class PersonalQuoteDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'personal_quote_id' => null,
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'utm_content' => null,
            'utm_term' => null,
        ];
    }

    public function withUtm(
        string $source = 'google',
        string $medium = 'cpc',
        string $campaign = 'summer-sale',
    ): static {
        return $this->state([
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
        ]);
    }
}
