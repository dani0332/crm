<?php

namespace Database\Factories;

use App\Models\CarPlan;
use App\Models\CarQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarQuoteFactory extends Factory
{
    protected $model = CarQuote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate UUID with 8-10 random characters
        $uuidLength = $this->faker->numberBetween(8, 10);
        $uuid = $this->faker->regexify('[A-Za-z0-9]{' . $uuidLength . '}');
        $uuid = strtoupper($uuid);

        // Create a CarPlan using factory (which automatically creates an InsuranceProvider)
        // This gives us both insurance_provider_id and plan_id
        $carPlan = CarPlan::factory()->create();

        return [
            'uuid' => $uuid,
            'code' => 'CAR-' . $uuid,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile_no' => $this->faker->phoneNumber(),
            'dob' => $this->faker->date(),
            'source' => $this->faker->randomElement(['TPL_RENEWALS', 'TPL_COMP', 'TM_ORGANIC', 'REVIVAL', 'test']),
            'device' => $this->faker->randomElement(['web', 'mobile', 'tablet']),
            'insurance_provider_id' => $carPlan->provider_id,
            'plan_id' => $carPlan->id,
            'quote_status_id' => null,
            'payment_status_id' => null,
            'advisor_id' => null,
            'created_by_id' => null,
            'updated_by_id' => null,
        ];
    }

    /**
     * Associate the quote with a specific car plan.
     * This will automatically set both plan_id and insurance_provider_id.
     *
     * @param int|CarPlan $carPlan
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forCarPlan($carPlan)
    {
        return $this->state(function (array $attributes) use ($carPlan) {
            $plan = $carPlan instanceof CarPlan ? $carPlan : CarPlan::findOrFail($carPlan);
            
            return [
                'plan_id' => $plan->id,
                'insurance_provider_id' => $plan->provider_id,
            ];
        });
    }
}
