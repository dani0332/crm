<?php

namespace Database\Factories;

use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SendUpdateLog>
 */
class SendUpdateLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'SU-'.strtoupper($this->faker->unique()->lexify('??????')),
            'quote_uuid' => $this->faker->uuid(),
            'quote_type_id' => QuoteTypeId::Car,
            'status' => SendUpdateLogStatusEnum::NEW_REQUEST,
            'category_id' => null,
            'option_id' => null,
        ];
    }
}
