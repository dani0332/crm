<?php

namespace Tests\Helpers;

use App\Enums\QuoteTypeId;
use App\Models\DeviceQuote;
use App\Models\PersonalQuote;
use App\Services\CapiService;
use Mockery;
use Mockery\MockInterface;

class DeviceQuoteMockHelper
{
    /**
     * Mock CapiService to simulate external API behavior.
     */
    public static function mockCapiService(string $testUuid): MockInterface
    {
        $mock = Mockery::mock(CapiService::class);
        $mock->shouldReceive('request')
            ->once()
            ->with('/api/device/create', 'post', Mockery::type('array'))
            ->andReturnUsing(function ($endpoint, $method, $data) use ($testUuid) {
                return self::simulateCapiResponse($testUuid, $data);
            });

        // Bind the mock to the service container using fully-qualified class name
        // This works for both constructor injection and facade access
        app()->instance(CapiService::class, $mock);
        // Also bind the facade accessor string for facade resolution
        app()->instance('CapiService', $mock);

        return $mock;
    }

    /**
     * Simulate what the CAPI service does: creates records and returns response.
     */
    private static function simulateCapiResponse(string $testUuid, array $data): object
    {
        $personalQuote = PersonalQuote::create([
            'uuid' => $testUuid,
            'quote_type_id' => QuoteTypeId::Device,
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'mobile_no' => $data['mobileNo'],
            'source' => $data['source'] ?? 'TEST',
            'device' => $data['device'] ?? 'DESKTOP',
            'code' => 'TEST-'.uniqid(),
            'created_by_id' => auth()->user()->id,
            'advisor_id' => $data['advisorId'] ?? null,
        ]);

        DeviceQuote::create([
            'personal_quote_id' => $personalQuote->id,
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'mobile_no' => $data['mobileNo'],
            'month_of_purchase' => $data['purchaseMonth'],
            'year_of_purchase' => $data['purchaseYear'],
            'make_id' => $data['phoneMakeId'],
            'model_id' => $data['phoneModelId'],
            'imei' => $data['imei'],
        ]);

        return (object) [
            'uuid' => $testUuid,
            'message' => null,
            'errors' => null,
        ];
    }
}
