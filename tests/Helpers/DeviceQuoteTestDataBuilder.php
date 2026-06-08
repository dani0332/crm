<?php

namespace Tests\Helpers;

class DeviceQuoteTestDataBuilder
{
    /**
     * Build default device quote form data for create/update.
     */
    public static function buildQuoteData(array $overrides = [], array $lookups = []): array
    {
        $currentYear = (int) date('Y');
        $defaults = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'user@gmail.com',
            'mobile_no' => '+971501234567',
            'month_of_purchase' => '6',
            'year_of_purchase' => (string) $currentYear,
            'make_id' => $lookups['make_id'] ?? 1,
            'model_id' => $lookups['model_id'] ?? 1,
            'imei' => '123456789012345',
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Build minimal valid data for validation tests (all required fields).
     */
    public static function buildMinimalValidData(array $lookups = []): array
    {
        return self::buildQuoteData([], $lookups);
    }
}
