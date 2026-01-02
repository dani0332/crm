<?php

namespace Tests\Helpers;

use App\Enums\GenericRequestEnum;

class LifeQuoteTestDataBuilder
{
    /**
     * Build default life quote request data.
     */
    public static function buildQuoteData(array $overrides = [], array $lookups = []): array
    {
        $defaults = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@gmail.com',
            'mobile_no' => '+971501234567',
            'dob' => '1990-01-15',
            'sum_insured_value' => 100000,
            'is_smoker' => 0,
            'gender' => GenericRequestEnum::MALE_SINGLE,
            'others_info' => 'Test information',
            'height' => 175,
            'weight' => 75,
            'bmi' => 24.5,
            'age' => 34,
        ];

        // Merge lookups if provided
        if (! empty($lookups)) {
            $defaults = array_merge($defaults, [
                'nationality_id' => $lookups['nationality_id'] ?? null,
                'sum_insured_currency_id' => $lookups['currency_id'] ?? null,
                'marital_status_id' => $lookups['marital_status_id'] ?? null,
                'purpose_of_insurance_id' => $lookups['purpose_of_insurance_id'] ?? null,
                'number_of_years_id' => $lookups['number_of_years_id'] ?? null,
            ]);
        }

        return array_merge($defaults, $overrides);
    }

    /**
     * Build minimal valid quote data (for validation tests).
     */
    public static function buildMinimalValidData(array $lookups): array
    {
        return self::buildQuoteData([], $lookups);
    }

    /**
     * Build quote data for a female smoker.
     */
    public static function buildFemaleSmokerData(array $overrides = [], array $lookups = []): array
    {
        return self::buildQuoteData(array_merge([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@gmail.com',
            'is_smoker' => 1,
            'gender' => GenericRequestEnum::FEMALE,
            'sum_insured_value' => 200000,
        ], $overrides), $lookups);
    }
}
