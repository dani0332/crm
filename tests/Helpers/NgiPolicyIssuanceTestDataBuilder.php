<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;

/**
 * Test data builder for NGI Device policy issuance tests.
 *
 * Provides factory methods for creating test data with sensible defaults
 * and easy override capabilities for specific test scenarios.
 */
class NgiPolicyIssuanceTestDataBuilder
{
    /**
     * Build default device quote data for NGI policy issuance tests.
     *
     * @param  array  $overrides  Custom field values
     * @param  array  $lookups  Lookup IDs from seeded tables
     */
    public static function buildDeviceQuoteData(array $overrides = [], array $lookups = []): array
    {
        $defaults = [
            'code' => 'DEV-'.uniqid(),
            'uuid' => 'test-device-quote-uuid-'.uniqid(),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'mobile_no' => '+971501234567',
            'dob' => '1990-01-15',
            'policy_start_date' => now()->format('Y-m-d'),
            'policy_expiry_date' => now()->addYear()->format('Y-m-d'),
            'insurer_quote_number' => 'NGI-Q-'.uniqid(),
            'plan_id' => 1,
            'quote_status_id' => 1,
            'quote_type_id' => QuoteTypes::DEVICE->value,
        ];

        if (! empty($lookups)) {
            $defaults = array_merge($defaults, [
                'nationality_id' => $lookups['nationality_id'] ?? null,
                'insurance_provider_id' => $lookups['insurance_provider_id'] ?? null,
            ]);
        }

        return array_merge($defaults, $overrides);
    }

    /**
     * Build device quote (sub-quote) data.
     *
     * @param  array  $overrides  Custom field values
     */
    public static function buildDeviceSubQuoteData(array $overrides = []): array
    {
        $defaults = [
            'imei' => '123456789012345',
            'purchase_date' => now()->subMonths(1)->format('Y-m-d'),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Build customer data for NGI policy issuance.
     *
     * @param  array  $overrides  Custom field values
     */
    public static function buildCustomerData(array $overrides = []): array
    {
        $defaults = [
            'emirates_id_number' => '784-'.rand(1000, 9999).'-'.rand(1000000, 9999999).'-'.rand(1, 9),
            'emirates_id_expiry_date' => now()->addYears(2)->format('Y-m-d'),
            'dob' => '1990-01-15',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'mobile_no' => '+971501234567',
            'address' => '123 Test Street, Dubai, UAE',
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Build payment data for NGI policy issuance.
     *
     * @param  array  $overrides  Custom field values
     */
    public static function buildPaymentData(array $overrides = []): array
    {
        $defaults = [
            'code' => 'PAY-'.uniqid(),
            'total_amount' => 500.00,
            'payment_status_id' => 1, // Paid
            'is_main_lead_payment' => true,
            'insurer_invoice_date' => now()->format('Y-m-d'),
            'insurer_tax_number' => 'TAX-'.uniqid(),
            'insurer_commmission_invoice_number' => 'COMM-'.uniqid(),
            'discount_value' => 0.00,
            'commission_vat_applicable' => 5.00,
            'commission_vat_not_applicable' => 0.00,
            'commission' => 50.00,
            'commission_vat' => 2.50,
            'commmission_percentage' => 10.00,
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Build insured data for latestInsured relationship.
     *
     * @param  array  $overrides  Custom field values
     */
    public static function buildInsuredData(array $overrides = []): array
    {
        $defaults = [
            'id_type' => 'emiratesId',
            'id_number' => '784-'.rand(1000, 9999).'-'.rand(1000000, 9999999).'-'.rand(1, 9),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'mobile_no' => '+971501234567',
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Build policy issuance process data.
     *
     * @param  array  $overrides  Custom field values
     */
    public static function buildPolicyIssuanceData(array $overrides = []): array
    {
        $defaults = [
            'quote_type' => QuoteTypes::DEVICE->value,
            'status' => PolicyIssuanceEnum::PENDING_STATUS,
            'completed_step' => null,
            'message' => null,
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Build NGI API response for CreatePolicyFromQuote.
     *
     * @param  bool  $success  Whether the response should be successful
     * @param  array  $overrides  Custom field values
     */
    public static function buildCreatePolicyApiResponse(bool $success = true, array $overrides = []): object
    {
        if ($success) {
            $defaults = [
                'isSuccess' => true,
                'statusMessage' => 'Policy created successfully',
                'policy_no' => 'NGI-POL-'.uniqid(),
                'policy_start_dt' => now()->format('Y-m-d'),
                'policy_end_dt' => now()->addYear()->format('Y-m-d'),
                'premium' => 500.00,
            ];
        } else {
            $defaults = [
                'isSuccess' => false,
                'statusMessage' => 'Policy creation failed',
                'errorCode' => 'ERR_INVALID_QUOTE',
            ];
        }

        return (object) array_merge($defaults, $overrides);
    }

    /**
     * Build NGI API response for GetPolicyDocuments.
     *
     * @param  bool  $success  Whether the response should be successful
     * @param  array  $overrides  Custom field values
     */
    public static function buildGetPolicyDocumentsApiResponse(bool $success = true, array $overrides = []): object
    {
        if ($success) {
            $defaults = [
                'isSuccess' => true,
                'statusMessage' => 'Documents retrieved successfully',
                'policy_no' => 'NGI-POL-'.uniqid(),
                'policy_certificate_url' => 'https://ngi-api.example.com/documents/policy.pdf',
                'premium_inv_doc_url' => 'https://ngi-api.example.com/documents/invoice.pdf',
                'commision_inv_doc_url' => 'https://ngi-api.example.com/documents/commission.pdf',
            ];
        } else {
            $defaults = [
                'isSuccess' => false,
                'statusMessage' => 'Documents not found',
                'errorCode' => 'ERR_DOCS_NOT_READY',
            ];
        }

        return (object) array_merge($defaults, $overrides);
    }

    /**
     * Build minimal valid quote data for validation tests.
     *
     * @param  array  $lookups  Lookup IDs from seeded tables
     */
    public static function buildMinimalValidData(array $lookups): array
    {
        return self::buildDeviceQuoteData([], $lookups);
    }

    /**
     * Build mock quote object for unit tests.
     *
     * @param  array  $overrides  Custom field values
     */
    public static function createMockQuote(array $overrides = []): object
    {
        $defaults = self::buildDeviceQuoteData($overrides);

        return (object) $defaults;
    }

    /**
     * Build mock customer object for unit tests.
     *
     * @param  array  $overrides  Custom field values
     */
    public static function createMockCustomer(array $overrides = []): object
    {
        $defaults = self::buildCustomerData($overrides);

        return (object) $defaults;
    }

    /**
     * Build mock device quote object for unit tests.
     *
     * @param  array  $overrides  Custom field values
     */
    public static function createMockDeviceQuote(array $overrides = []): object
    {
        $defaults = self::buildDeviceSubQuoteData($overrides);

        return (object) $defaults;
    }

    /**
     * Build mock insured object for unit tests.
     *
     * @param  array  $overrides  Custom field values
     */
    public static function createMockInsured(array $overrides = []): object
    {
        $defaults = self::buildInsuredData($overrides);

        return (object) $defaults;
    }

    /**
     * Build mock payment object for unit tests.
     *
     * @param  array  $overrides  Custom field values
     */
    public static function createMockPayment(array $overrides = []): object
    {
        $defaults = self::buildPaymentData($overrides);

        return (object) $defaults;
    }
}
