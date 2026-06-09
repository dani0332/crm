<?php

namespace Database\Factories;

use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarQuoteRequestDetail>
 */
class CarQuoteRequestDetailFactory extends Factory
{
    protected $model = CarQuoteRequestDetail::class;

    public function configure(): static
    {
        return $this->afterMaking(function (CarQuoteRequestDetail $detail) {
            if (app()->environment('testing')) {
                $detail->setConnection('sqlite');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'car_quote_request_id' => CarQuote::factory(),
            'engagement_level' => null,
            'engagement_level_updated_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forCarQuote(CarQuote $carQuote): static
    {
        return $this->state(fn (array $attributes) => [
            'car_quote_request_id' => $carQuote->getKey(),
        ]);
    }
}
