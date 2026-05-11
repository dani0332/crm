<?php

namespace Tests\Helpers;

use App\Enums\QuoteTypes;
use App\Models\DeviceQuote;
use App\Models\PersonalQuote;
use App\Services\CapiService;
use Mockery;
use Mockery\MockInterface;

class DeviceQuoteMockHelper
{
    /**
     * Mock CapiService (via Capi facade) to simulate device create API behavior.
     * When the service calls Capi::request('/api/v1/device/create', 'post', $data),
     * we create PersonalQuote + DeviceQuote locally and return success with uuid.
     */
    public static function mockCapiDeviceCreate(string $testUuid): MockInterface
    {
        $mock = Mockery::mock(CapiService::class)->makePartial();
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('request')
            ->with('/api/v1/device/create', 'post', Mockery::type('array'))
            ->andReturnUsing(function ($path, $method, $data) use ($testUuid) {
                return self::simulateDeviceCreateResponse($testUuid, $data);
            });

        app()->instance('CapiService', $mock);

        return $mock;
    }

    /**
     * Simulate CAPI device create: create PersonalQuote and DeviceQuote, return response object.
     */
    private static function simulateDeviceCreateResponse(string $testUuid, array $data): object
    {
        $personalQuote = PersonalQuote::create([
            'uuid' => $testUuid,
            'quote_type_id' => (int) QuoteTypes::DEVICE->id(),
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'mobile_no' => $data['mobileNo'],
            'source' => $data['source'] ?? 'TEST',
            'device' => $data['device'] ?? 'DESKTOP',
            'code' => 'DEV-'.uniqid(),
            'created_by_id' => auth()->id(),
            'advisor_id' => $data['advisorId'] ?? null,
        ]);

        $purchaseDate = sprintf('%04d-%02d-01', $data['purchaseYear'] ?? date('Y'), $data['purchaseMonth'] ?? 1);

        DeviceQuote::create([
            'personal_quote_id' => $personalQuote->id,
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'mobile_no' => $data['mobileNo'],
            'month_of_purchase' => (string) ($data['purchaseMonth'] ?? '1'),
            'year_of_purchase' => (string) ($data['purchaseYear'] ?? date('Y')),
            'purchase_date' => $purchaseDate,
            'make_id' => $data['phoneMakeId'] ?? null,
            'model_id' => $data['phoneModelId'] ?? null,
            'imei' => $data['imei'] ?? null,
        ]);

        return (object) [
            'uuid' => $testUuid,
            'message' => 'Quote is created successfully.',
        ];
    }
}
