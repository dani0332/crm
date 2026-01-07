<?php

namespace Tests\Helpers;

use App\Enums\QuoteTypes;
use Illuminate\Support\Arr;

class PrimaryEmailSwitchTestDataBuilder
{

    /**
     * Build default car quote data for Tokio policy issuance tests.
     */
    public static function buildQuoteData(array $overrides = [], QuoteTypes $quoteType): array
    {
        $quoteUUID = uniqid();

        $defaults = [
            'code' => "$quoteType->name-$quoteUUID",
            'uuid' => "test-quote-uuid-$quoteUUID",
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'mobile_no' => '+971501234567',
            'dob' => '1990-01-15'
        ];

        $data = array_merge($defaults, $overrides, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Arr::only($data, [
            'code',
            'uuid',
            'first_name',
            'last_name',
            'email',
            'mobile_no',
            'customer_id',
            'created_at',
            'updated_at'
        ]);
    }

    /**
     * Build customer data for Tokio policy issuance.
     */
    public static function buildCustomerData(array $overrides = []): array
    {
        $defaults = [
            'emirates_id_number' => '784-'.rand(1000, 9999).'-'.rand(1000000, 9999999).'-'.rand(1, 9),
            'dob' => '1990-01-15',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'mobile_no' => '+971501234567',
        ];

        return array_merge($defaults, $overrides);
    }

    public static function buildCustomerAdditionalContactData(int $customerId, string $email): array
    {
        return [
            'customer_id' => $customerId,
            'key' => 'email',
            'value' => $email,
        ];
    }
}