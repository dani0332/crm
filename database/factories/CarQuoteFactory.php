<?php

namespace Database\Factories;

use App\Models\CarQuote;
use DB;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarQuoteFactory extends Factory
{
    protected $model = CarQuote::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $carMakeIds = DB::table('car_make')->pluck('id');
        $carModelIds = DB::table('car_model')->pluck('id');
        $nationalityIds = DB::table('nationality')->pluck('id');
        $quoteStatusIds = DB::table('quote_status')->where('text', ['Fake', 'Duplicate', 'Followed Up', 'Quoted', 'Lost', 'Policy Issued'])->where('is_active', 1)->pluck('id');
        $quoteBatchIds = DB::table('quote_batches')->pluck('id');
        $tierIds = DB::table('tiers')->pluck('id');
        $sources = DB::table('car_quote_request')->distinct('source')->pluck('source');
        $uuid = $this->faker->regexify('[A-Za-z0-9]{8}');
        $users = DB::table('users')->where('team_id', 2)->pluck('id');

        return [
            'uuid' => strtoupper($uuid),
            'is_ecommerce' => $this->faker->boolean(),
            'car_value' => $this->faker->numberBetween(5000, 200000),
            'car_make_id' => $this->faker->randomElement(($carMakeIds)),
            'car_model_id' => $this->faker->randomElement(($carModelIds)),
            'first_name' => $this->faker->name(),
            'last_name' => $this->faker->name(),
            'email' => $this->faker->unique()->email,
            'mobile_no' => $this->faker->unique()->phoneNumber,
            'source' => $this->faker->randomElement(($sources)),
            'nationality_id' => $this->faker->randomElement(($nationalityIds)),
            'quote_status_id' => $this->faker->randomElement(($quoteStatusIds)),
            'created_at' => now(),
            'code' => 'CAR-'.strtoupper($uuid),
            'created_by' => 17,
            'renewal_expiry_date' => $this->faker->dateTime('now'),
            'tier_id' => $this->faker->randomElement(($tierIds)),
            'quote_batch_id' => $this->faker->randomElement(($quoteBatchIds)),
            'advisor_id' => $this->faker->randomElement($users),
        ];
    }
}
