<?php

namespace Database\Factories;

use App\Models\CarQuote;
use App\Models\VehicleDriverDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleDriverDetailFactory extends Factory
{
    protected $model = VehicleDriverDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quoteable_type' => CarQuote::class,
            'quoteable_id' => null,
            'driver_first_name' => $this->faker->firstName(),
            'driver_last_name' => $this->faker->lastName(),
            'driver_dob' => $this->faker->date('Y-m-d', '-25 years'),
            'driver_gender' => $this->faker->randomElement(['male', 'female']),
            'driver_license_number' => $this->faker->numerify('########'),
            'driver_license_issue_date' => $this->faker->date('Y-m-d', '-5 years'),
            'driver_license_expiry_date' => $this->faker->date('Y-m-d', '+5 years'),
            'driver_license_issue_place' => $this->faker->randomElement(['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman']),
            'traffic_code_number' => $this->faker->numerify('########'),
            'vehicle_plate_number' => $this->faker->numerify('#####'),
            'vehicle_plate_code' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'vehicle_color' => $this->faker->randomElement(['White', 'Black', 'Silver', 'Blue']),
            'vehicle_engine_number' => $this->faker->bothify('??####??'),
            'first_registration_date' => $this->faker->date('Y-m-d', '-3 years'),
        ];
    }

    /**
     * Indicate that the vehicle driver detail has an Emirates ID.
     */
    public function withEmiratesId(): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_eid_number' => '784198512345671',
        ]);
    }

    /**
     * Indicate that the vehicle driver detail is for a male driver.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_gender' => 'male',
        ]);
    }

    /**
     * Indicate that the vehicle driver detail is for a female driver.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_gender' => 'female',
        ]);
    }

    /**
     * Indicate that the vehicle driver detail is for a specific quote.
     */
    public function forQuote($quoteId, $quoteType = CarQuote::class): static
    {
        return $this->state(fn (array $attributes) => [
            'quoteable_type' => $quoteType,
            'quoteable_id' => $quoteId,
        ]);
    }
}
