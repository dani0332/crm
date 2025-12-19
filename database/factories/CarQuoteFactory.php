<?php

namespace Database\Factories;

use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

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
            'source' => $this->faker->randomElement(['IMCRM']),
            'device' => $this->faker->randomElement(['web']),
            'premium' => 1000,
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

    /**
     * Create a CarQuote using SQLite connection for tests.
     * This method handles models with hardcoded MySQL connections by inserting
     * directly via DB facade and then loading the model with SQLite connection.
     *
     * @param array $attributes If 'uuid' is provided, it will be used. If 'code' is provided, it will be used.
     *                          Otherwise, UUID and code will be auto-generated.
     * @return CarQuote
     */
    public function createForSqlite(array $attributes = []): CarQuote
    {
        $db = DB::connection('sqlite');
        
        // Generate UUID if not provided
        if (!isset($attributes['uuid'])) {
            $uuidLength = $this->faker->numberBetween(8, 10);
            $uuid = strtoupper($this->faker->regexify('[A-Za-z0-9]{' . $uuidLength . '}'));
        } else {
            $uuid = $attributes['uuid'];
            unset($attributes['uuid']); // Remove from attributes to avoid duplication
        }
        
        // Generate code if not provided
        if (!isset($attributes['code'])) {
            $code = 'CAR-' . $uuid;
        } else {
            $code = $attributes['code'];
            unset($attributes['code']); // Remove from attributes to avoid duplication
        }
        
        // Build quote attributes
        $quoteAttributes = array_merge([
            'uuid' => $uuid,
            'code' => $code,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile_no' => $this->faker->phoneNumber(),
            'dob' => $this->faker->date(),
            'source' => $this->faker->randomElement(['IMCRM']),
            'device' => $this->faker->randomElement(['web']),
            'premium' => 1000,
            'quote_status_id' => null,
            'payment_status_id' => null,
            'advisor_id' => null,
            'created_by_id' => null,
            'updated_by_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);
        
        // Insert via SQLite connection
        $quoteId = $db->table('car_quote_request')->insertGetId($quoteAttributes);
        
        // Load and return model with SQLite connection
        return CarQuote::on('sqlite')->find($quoteId);
    }
}
