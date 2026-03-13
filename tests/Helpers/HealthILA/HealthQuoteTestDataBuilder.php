<?php

namespace Tests\Helpers\HealthILA;

class HealthQuoteTestDataBuilder
{
    /**
     * Build default health quote request data.
     */
    public static function buildQuoteData(array $overrides = [], array $lookups = []): array
    {
        $defaults = [
            'first_name' => 'Ahmed',
            'last_name' => 'Al-Mansouri',
            'email' => 'ahmed.mansouri@gmail.com',
            'mobile_no' => '+971501234567',
            'dob' => '1990-01-15',
            'nationality_id' => $lookups['nationality_id'] ?? 1,
            'price_starting_from' => 4690.00,
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Build minimal valid quote data (for validation tests).
     */
    public static function buildMinimalValidData(array $lookups = []): array
    {
        return self::buildQuoteData([], $lookups);
    }

    /**
     * Build quote data for a female customer.
     */
    public static function buildFemaleCustomerData(array $overrides = [], array $lookups = []): array
    {
        return self::buildQuoteData(array_merge([
            'first_name' => 'Fatima',
            'last_name' => 'Al-Mansoori',
            'email' => 'fatima.al.mansoori@gmail.com',
        ], $overrides), $lookups);
    }

    /**
     * Build quote data with custom price starting from.
     */
    public static function buildWithPrice(float $price, array $overrides = [], array $lookups = []): array
    {
        return self::buildQuoteData(array_merge([
            'price_starting_from' => $price,
        ], $overrides), $lookups);
    }

    /**
     * Build quote data for SIC lead with plan and premium.
     */
    public static function buildSICLeadData(int $planId, float $premium, array $overrides = [], array $lookups = []): array
    {
        return self::buildQuoteData(array_merge([
            'plan_id' => $planId,
            'premium' => $premium,
            'sic_advisor_requested' => 1,
        ], $overrides), $lookups);
    }

    /**
     * Build quote data with PEC tag.
     */
    public static function buildWithPecTag(array $overrides = [], array $lookups = []): array
    {
        return self::buildQuoteData(array_merge([
            'has_pec_tag' => true,
        ], $overrides), $lookups);
    }

    /**
     * Get payload data for API calls.
     */
    public static function getApiPayload(array $overrides = [], array $lookups = []): array
    {
        $data = self::buildQuoteData($overrides, $lookups);

        return [
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'email' => $data['email'],
            'mobileNo' => $data['mobile_no'],
            'dob' => $data['dob'],
            'nationalityId' => $data['nationality_id'],
            'quoteTypeId' => 3, // Health quote type (QuoteTypeId::Health)
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => 'IMCRM',
            'referenceUrl' => config('constants.APP_URL') ?? 'http://localhost',
            'priceStartingFrom' => $data['price_starting_from'] ?? null,
        ];
    }
}
