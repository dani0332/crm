<?php

namespace Database\Factories;

use App\Enums\QuoteTypeId;
use App\Models\CommunicationEventLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationEventLog>
 */
class CommunicationEventLogFactory extends Factory
{
    protected $model = CommunicationEventLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote_uuid' => $this->faker->unique()->bothify('????????'),
            'quote_type_id' => QuoteTypeId::Car,
            'event_channel' => $this->faker->randomElement(['Email', 'WhatsApp', 'myAlfred']),
            'communication_type' => $this->faker->words(3, true),
            'action_event' => 'BUY_NOW',
        ];
    }
}
