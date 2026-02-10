<?php

use App\Events\QuoteEmailUpdated;
use App\Models\DeviceQuote;
use App\Models\PersonalQuote;
use App\Enums\QuoteTypes;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\DeviceQuoteMockHelper;
use Tests\Helpers\DeviceQuoteTestDataBuilder;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->lookups = TestDataSeeder::seedDeviceQuoteLookups();
    $this->user = TestDataSeeder::createAdminUser();
    TestDataSeeder::seedDeviceQuotePermissions();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
});

test('can create a device quote using actual controller method', function () {
    $testUuid = 'test-device-quote-'.uniqid();
    $quoteData = DeviceQuoteTestDataBuilder::buildQuoteData([], $this->lookups);

    DeviceQuoteMockHelper::mockCapiDeviceCreate($testUuid);
    Event::fake([QuoteEmailUpdated::class]);

    $response = $this->post(route('device-quotes-store'), $quoteData);

    $personalQuote = PersonalQuote::where('uuid', $testUuid)->firstOrFail();
    expect($personalQuote->first_name)->toBe($quoteData['first_name'])
        ->and($personalQuote->last_name)->toBe($quoteData['last_name'])
        ->and($personalQuote->email)->toBe($quoteData['email'])
        ->and($personalQuote->quote_type_id)->toBe((int) QuoteTypes::DEVICE->id())
        ->and($personalQuote->created_by_id)->toBe($this->user->id);

    $deviceQuote = $personalQuote->deviceQuote;
    expect($deviceQuote)->not->toBeNull()
        ->and($deviceQuote->first_name)->toBe($quoteData['first_name'])
        ->and($deviceQuote->last_name)->toBe($quoteData['last_name'])
        ->and($deviceQuote->email)->toBe($quoteData['email'])
        ->and($deviceQuote->imei)->toBe($quoteData['imei'])
        ->and($deviceQuote->make_id)->toBe((int) $quoteData['make_id'])
        ->and($deviceQuote->model_id)->toBe((int) $quoteData['model_id']);
});

test('validates required fields when creating device quote', function () {
    $response = $this->post(route('device-quotes-store'), []);

    $response->assertSessionHasErrors([
        'first_name',
        'last_name',
        'email',
        'mobile_no',
        'month_of_purchase',
        'year_of_purchase',
        'make_id',
        'model_id',
        'imei',
    ]);

    $response->assertStatus(302);
});

test('validates imei must be 15 digits when creating device quote', function () {
    $quoteData = DeviceQuoteTestDataBuilder::buildQuoteData(['imei' => '123'], $this->lookups);

    $response = $this->post(route('device-quotes-store'), $quoteData);

    $response->assertSessionHasErrors(['imei']);
    $response->assertStatus(302);
});

test('can update a device quote', function () {
    $testUuid = 'test-device-update-'.uniqid();
    $createData = DeviceQuoteTestDataBuilder::buildQuoteData([], $this->lookups);

    // Create quote directly so it exists for the update (avoids relying on mock + POST across requests)
    $personalQuote = PersonalQuote::create([
        'uuid' => $testUuid,
        'quote_type_id' => (int) QuoteTypes::DEVICE->id(),
        'first_name' => $createData['first_name'],
        'last_name' => $createData['last_name'],
        'email' => $createData['email'],
        'mobile_no' => $createData['mobile_no'],
        'source' => 'TEST',
        'device' => 'DESKTOP',
        'code' => 'DEV-'.uniqid(),
        'created_by_id' => $this->user->id,
        'advisor_id' => $this->user->id,
    ]);

    DeviceQuote::create([
        'personal_quote_id' => $personalQuote->id,
        'first_name' => $createData['first_name'],
        'last_name' => $createData['last_name'],
        'email' => $createData['email'],
        'mobile_no' => $createData['mobile_no'],
        'month_of_purchase' => $createData['month_of_purchase'],
        'year_of_purchase' => $createData['year_of_purchase'],
        'purchase_date' => sprintf('%04d-%02d-01', $createData['year_of_purchase'], $createData['month_of_purchase']),
        'make_id' => $createData['make_id'],
        'model_id' => $createData['model_id'],
        'imei' => $createData['imei'],
    ]);

    $updateData = DeviceQuoteTestDataBuilder::buildQuoteData([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane.smith@gmail.com',
        'mobile_no' => '+971509876543',
        'month_of_purchase' => '12',
        'year_of_purchase' => '2024',
        'imei' => '987654321054321',
    ], $this->lookups);

    Event::fake([QuoteEmailUpdated::class]);

    $response = $this->put(route('device-quotes-update', ['smartphone' => $testUuid]), $updateData);


    $personalQuote->refresh();
    expect($personalQuote->first_name)->toBe('Jane')
        ->and($personalQuote->last_name)->toBe('Smith')
        ->and($personalQuote->email)->toBe('jane.smith@gmail.com');

    $deviceQuote = $personalQuote->deviceQuote;
    expect($deviceQuote->imei)->toBe('987654321054321')
        ->and($deviceQuote->make_id)->toBe((int) $updateData['make_id'])
        ->and($deviceQuote->model_id)->toBe((int) $updateData['model_id']);
});
