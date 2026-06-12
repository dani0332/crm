<?php

namespace Tests\Helpers\CyberILA;

class CyberQuoteTestDataBuilder
{
    /**
     * Build default cyber quote request data.
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
            'emirate_of_registration_id' => $lookups['emirate_id'] ?? 1,
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
     * Build quote data with custom coverage.
     */
    public static function buildWithCoverage(string $coverage, array $overrides = [], array $lookups = []): array
    {
        return self::buildQuoteData(array_merge([
            'coverage' => $coverage,
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
            'emirateOfRegistrationId' => $data['emirate_of_registration_id'],
            'quoteTypeId' => 19, // Cyber quote type (QuoteTypeId::Cyber)
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'source' => 'IMCRM',
            'referenceUrl' => config('constants.APP_URL') ?? 'http://localhost',
        ];
    }
}
