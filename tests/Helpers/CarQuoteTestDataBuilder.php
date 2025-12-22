<?php

namespace Tests\Helpers;

use App\Enums\CarRegistrationType;
use App\Enums\InsuranceProvidersEnum;

class CarQuoteTestDataBuilder
{
    /**
     * Build default car quote request data.
     */
    public static function buildCarQuoteData(array $overrides = [], array $lookups = []): array
    {
        $defaults = [
            'uuid' => 'test-car-quote-uuid-'.uniqid(),
            'code' => 'CQ-TEST-'.rand(100000, 999999),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@test.com',
            'mobile_no' => '+971501234567',
            'dob' => '1990-01-15',
            'registration_type' => CarRegistrationType::PERSONAL,
            'policy_issuance_automation_enabled' => true,
        ];

        // Merge lookups if provided
        if (! empty($lookups)) {
            $defaults = array_merge($defaults, $lookups);
        }

        return array_merge($defaults, $overrides);
    }

    /**
     * Build car quote data for RSA insurer.
     */
    public static function buildRSACarQuoteData(array $overrides = [], array $lookups = []): array
    {
        $insurerData = [
            'insurance_provider_code' => InsuranceProvidersEnum::RSA,
        ];

        return self::buildCarQuoteData(array_merge($insurerData, $overrides), $lookups);
    }

    /**
     * Build car quote data for AXA insurer.
     */
    public static function buildAXACarQuoteData(array $overrides = [], array $lookups = []): array
    {
        $insurerData = [
            'insurance_provider_code' => InsuranceProvidersEnum::AXA,
        ];

        return self::buildCarQuoteData(array_merge($insurerData, $overrides), $lookups);
    }
}

