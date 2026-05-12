<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Enums\ApplicationStorageEnums;
use App\Services\ApplicationStorageService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

/**
 * Mock helper for NGI Device policy issuance tests.
 *
 * Provides methods to fake HTTP responses, enable/disable automation,
 * and set up common test scenarios.
 */
class NgiPolicyIssuanceMockHelper
{
    /**
     * Mock NGI API to simulate successful API responses.
     */
    public static function mockNgiApiSuccess(): void
    {
        Http::fake([
            // Mock successful CreatePolicyFromQuote response
            '*/api/Policy/CreatePolicyFromQuoteBW' => Http::response([
                'isSuccess' => true,
                'statusMessage' => 'Policy created successfully',
                'policy_no' => 'NGI-POL-'.uniqid(),
                'policy_start_dt' => now()->format('Y-m-d'),
                'policy_end_dt' => now()->addYear()->format('Y-m-d'),
                'premium' => 500.00,
            ], 200),

            // Mock successful GetPolicyDocuments response
            '*/api/Policy/GetPolicyDocuments*' => Http::response([
                'isSuccess' => true,
                'statusMessage' => 'Documents retrieved successfully',
                'policy_no' => 'NGI-POL-'.uniqid(),
                'policy_certificate_url' => 'https://ngi-api.example.com/documents/policy.pdf',
                'premium_inv_doc_url' => 'https://ngi-api.example.com/documents/invoice.pdf',
                'commision_inv_doc_url' => 'https://ngi-api.example.com/documents/commission.pdf',
            ], 200),

            // Mock document download
            '*ngi-api.example.com/documents/*' => Http::response('PDF content here', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);
    }

    /**
     * Mock NGI API to simulate failed API responses.
     */
    public static function mockNgiApiFailure(): void
    {
        Http::fake([
            '*/api/Policy/CreatePolicyFromQuoteBW' => Http::response([
                'isSuccess' => false,
                'statusMessage' => 'Invalid quote reference number',
                'errorCode' => 'ERR_INVALID_QUOTE',
            ], 200),

            '*/api/Policy/GetPolicyDocuments*' => Http::response([
                'isSuccess' => false,
                'statusMessage' => 'Documents not found',
                'errorCode' => 'ERR_DOCS_NOT_READY',
            ], 200),
        ]);
    }

    /**
     * Mock NGI API to simulate timeout errors.
     */
    public static function mockNgiApiTimeout(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Connection timed out');
        });
    }

    /**
     * Mock NGI API to simulate 404 Not Found.
     */
    public static function mockNgiApi404(): void
    {
        Http::fake([
            '*/api/Policy/*' => Http::response('', 404),
        ]);
    }

    /**
     * Mock NGI API to simulate 500 Server Error.
     */
    public static function mockNgiApi500(): void
    {
        Http::fake([
            '*/api/Policy/*' => Http::response([
                'message' => 'Internal Server Error',
            ], 500),
        ]);
    }

    /**
     * Mock CreatePolicyFromQuote to succeed, GetPolicyDocuments to fail.
     * Useful for testing partial flow completion.
     */
    public static function mockNgiApiPartialSuccess(): void
    {
        Http::fake([
            '*/api/Policy/CreatePolicyFromQuoteBW' => Http::response([
                'isSuccess' => true,
                'statusMessage' => 'Policy created successfully',
                'policy_no' => 'NGI-POL-'.uniqid(),
                'policy_start_dt' => now()->format('Y-m-d'),
                'policy_end_dt' => now()->addYear()->format('Y-m-d'),
            ], 200),

            '*/api/Policy/GetPolicyDocuments*' => Http::response([
                'isSuccess' => false,
                'statusMessage' => 'Documents not ready yet',
                'errorCode' => 'ERR_DOCS_PENDING',
            ], 200),
        ]);
    }

    /**
     * Mock specific CreatePolicyFromQuote response.
     *
     * @param  array  $responseData  Custom response data
     * @param  int  $statusCode  HTTP status code
     */
    public static function mockCreatePolicyResponse(array $responseData, int $statusCode = 200): void
    {
        Http::fake([
            '*/api/Policy/CreatePolicyFromQuoteBW' => Http::response($responseData, $statusCode),
        ]);
    }

    /**
     * Mock specific GetPolicyDocuments response.
     *
     * @param  array  $responseData  Custom response data
     * @param  int  $statusCode  HTTP status code
     */
    public static function mockGetPolicyDocumentsResponse(array $responseData, int $statusCode = 200): void
    {
        Http::fake([
            '*/api/Policy/GetPolicyDocuments*' => Http::response($responseData, $statusCode),
        ]);
    }

    /**
     * Enable NGI smartphone automation in application storage.
     */
    public static function enableNgiAutomation(): void
    {
        $db = DB::connection('sqlite');
        $db->table('application_storage')->updateOrInsert(
            ['key_name' => ApplicationStorageEnums::ENABLE_NGI_SMARTPHONE_POLICY_ISSUANCE],
            ['value' => '1', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    /**
     * Disable NGI smartphone automation in application storage.
     */
    public static function disableNgiAutomation(): void
    {
        $db = DB::connection('sqlite');
        $db->table('application_storage')->updateOrInsert(
            ['key_name' => ApplicationStorageEnums::ENABLE_NGI_SMARTPHONE_POLICY_ISSUANCE],
            ['value' => '0', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    /**
     * Enable NGI retry timeout automation.
     */
    public static function enableNgiRetryTimeout(): void
    {
        $db = DB::connection('sqlite');
        $db->table('application_storage')->updateOrInsert(
            ['key_name' => ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_NGI_SMARTPHONE_POLICY_ISSUANCE],
            ['value' => '1', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    /**
     * Create mock for ApplicationStorageService.
     *
     * @param  bool  $automationEnabled  Whether automation should be enabled
     * @param  bool  $retryEnabled  Whether retry on timeout should be enabled
     */
    public static function mockApplicationStorageService(bool $automationEnabled = true, bool $retryEnabled = true): MockInterface
    {
        $mock = \Mockery::mock(ApplicationStorageService::class);
        $mock->shouldReceive('getValueByKey')
            ->with(ApplicationStorageEnums::ENABLE_NGI_SMARTPHONE_POLICY_ISSUANCE)
            ->andReturn($automationEnabled);
        $mock->shouldReceive('getValueByKey')
            ->with(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_NGI_SMARTPHONE_POLICY_ISSUANCE)
            ->andReturn($retryEnabled);

        return $mock;
    }

    /**
     * Seed NGI-specific lookups for testing.
     *
     * @return array Array with lookup IDs
     */
    public static function seedNgiLookups(): array
    {
        $db = DB::connection('sqlite');

        // Create Nationality
        $nationalityId = $db->table('nationality')->where('text', 'UAE')->value('id');
        if (! $nationalityId) {
            $nationalityId = $db->table('nationality')->insertGetId([
                'text' => 'UAE',
                'code' => 'UAE',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Insurance Provider (NGI)
        $insurerId = $db->table('insurance_provider')->where('code', 'NGI')->value('id');
        if (! $insurerId) {
            $insurerId = $db->table('insurance_provider')->insertGetId([
                'code' => 'NGI',
                'text' => 'National General Insurance',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'nationality_id' => $nationalityId,
            'insurance_provider_id' => $insurerId,
        ];
    }
}
