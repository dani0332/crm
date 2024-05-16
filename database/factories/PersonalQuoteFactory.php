<?php

namespace Database\Factories;

use App\Models\PersonalQuote;
use App\Models\QuoteType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class PersonalQuoteFactory extends Factory
{
    protected $model = PersonalQuote::class;

    public function definition()
    {
        $priceType = $this->faker->randomElement(['price_vat_applicable', 'price_vat_not_applicable']);
        $commissionType = $this->faker->randomElement(['commission_vat_applicable', 'commission_vat_not_applicable']);
        $table = $this->faker->randomElement(['car_quote_request', 'health_quote_request']);
        var_dump($table);

        $record = DB::table($table)
            ->join('payments', $table.'.id', '=', 'payments.paymentable_id')
            ->whereNotExists(function ($query) use ($table) {
                $query->select(DB::raw(1))
                    ->from('personal_quotes')
                    ->whereRaw($table.'.code = personal_quotes.code');
            })
            ->select($table.'.*')
            ->inRandomOrder()
            ->first();

        // Check if a record is found before accessing its properties
        if ($record !== null) {
            // Ensure that the QuoteType entry exists before assigning its ID
            $quoteTypeShortCode = ($table === 'car_quote_request') ? 'CAR' : 'HEA';
            $quoteType = QuoteType::where('short_code', $quoteTypeShortCode)->first();
            var_dump(str($quoteType->id));
            if ($quoteType !== null) {

                var_dump($record->code);
                // Check if a record with the same code already exists in personal_quotes
                $personalQuote = PersonalQuote::where('code', $record->code)->first();
                if ($personalQuote === null) {
                    return [
                        'uuid' => ''.$record->uuid.'',
                        'advisor_id' => $record->advisor_id,
                        'first_name' => ''.$record->first_name.'',
                        'last_name' => ''.$record->last_name.'',
                        'email' => ''.$record->email.'',
                        'mobile_no' => ''.$record->mobile_no,
                        'source' => ''.$record->source.'',
                        'device' => ''.$record->device.'',
                        'code' => ''.$record->code.'',
                        'quote_type_id' => $quoteType->id,
                        'policy_issuance_date' => ''.now().'',
                        'policy_number' => ''.$this->faker->randomNumber().'',
                        'customer_id' => $record->customer_id,
                        'created_at' => ''.now().'',
                        'updated_at' => ''.now().'',
                        'price_vat_applicable' => $record->$priceType === 'price_vat_applicable' ? $this->faker->numberBetween(500, 10000) : 0,
                        'price_vat_not_applicable' => $record->$priceType === 'price_vat_not_applicable' ? $this->faker->numberBetween(500, 10000) : 0,
                        'commission_vat_applicable' => $record->$commissionType === 'commission_vat_applicable' ? $this->faker->numberBetween(500, 10000) : 0,
                        'commission_vat_not_applicable' => $record->$commissionType === 'commission_vat_not_applicable' ? $this->faker->numberBetween(500, 10000) : 0,
                    ];
                }
            }
        }

        // If $record is null or a record with the same code already exists, return an empty array to skip insertion
        return [];
    }
}
