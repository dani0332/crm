<?php

namespace Database\Factories;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonalQuoteFactory extends Factory
{
    protected $model = PersonalQuote::class;

    public function definition(): array
    {
        $uuid = $this->faker->unique()->uuid();

        return [
            'uuid' => $uuid,
            'code' => 'PQ-'.strtoupper(substr($uuid, 0, 8)),
            'quote_type_id' => QuoteTypes::PET->id(),
            'customer_id' => null,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile_no' => '+971'.$this->faker->numerify('#########'),
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'device' => 'web',
            'advisor_id' => null,
            'assignment_type' => null,
            'quote_status_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forQuoteType($quoteTypeId): self
    {
        return $this->state(fn (array $attributes) => [
            'quote_type_id' => (int) $quoteTypeId,
        ]);
    }
}
