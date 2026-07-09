<?php

/**
 * Device quote feature tests. All mocking uses Mockery only (Mockery::mock(), Mockery::on(), etc.).
 */

use App\Services\Quotes\DeviceQuoteService;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\Helpers\DeviceQuoteTestDataBuilder;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->user = TestDataSeeder::createAdminUser();
    TestDataSeeder::seedDeviceQuotePermissions();
    $this->withoutMiddleware(VerifyCsrfToken::class);
    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
});

test('can create a device quote using mocked service', function () {
    $testUuid = 'test-device-quote-'.uniqid();
    $quoteData = DeviceQuoteTestDataBuilder::buildQuoteData([], ['make_id' => 1, 'model_id' => 1]);

    $mockService = Mockery::mock(DeviceQuoteService::class)->makePartial();
    $mockService->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $data) use ($quoteData) {
            return $data['first_name'] === $quoteData['first_name']
                && $data['last_name'] === $quoteData['last_name']
                && $data['email'] === $quoteData['email']
                && $data['imei'] === $quoteData['imei']
                && (int) $data['make_id'] === (int) $quoteData['make_id']
                && (int) $data['model_id'] === (int) $quoteData['model_id'];
        }))
        ->andReturn((object) ['uuid' => $testUuid, 'message' => 'Quote is created successfully.']);

    $this->app->instance(DeviceQuoteService::class, $mockService);

    $response = $this->post(route('device-quotes-store'), $quoteData);

    $response->assertRedirect(route('device-quotes-show', $testUuid));
    $response->assertSessionHas('message', 'Quote is created successfully.');
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
    $quoteData = DeviceQuoteTestDataBuilder::buildQuoteData(['imei' => '123'], ['make_id' => 1, 'model_id' => 1]);

    $response = $this->post(route('device-quotes-store'), $quoteData);

    $response->assertSessionHasErrors(['imei']);
    $response->assertStatus(302);
});

test('can update a device quote using mocked service', function () {
    $testUuid = 'test-device-update-'.uniqid();
    $updateData = DeviceQuoteTestDataBuilder::buildQuoteData([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane.smith@gmail.com',
        'mobile_no' => '+971509876543',
        'month_of_purchase' => '12',
        'year_of_purchase' => '2024',
        'imei' => '987654321054321',
    ], ['make_id' => 1, 'model_id' => 1]);

    $mockService = Mockery::mock(DeviceQuoteService::class)->makePartial();
    $mockService->shouldReceive('update')
        ->once()
        ->with($testUuid, Mockery::on(function (array $data) {
            return $data['first_name'] === 'Jane'
                && $data['last_name'] === 'Smith'
                && $data['imei'] === '987654321054321';
        }))
        ->andReturnNull();

    $this->app->instance(DeviceQuoteService::class, $mockService);

    $response = $this->put(route('device-quotes-update', ['smartphone' => $testUuid]), $updateData);

    $response->assertRedirect(route('device-quotes-show', $testUuid));
    $response->assertSessionHas('message', 'Quote is updated successfully.');
});

test('create device quote uses custom message from service when provided', function () {
    $testUuid = 'test-device-quote-'.uniqid();
    $quoteData = DeviceQuoteTestDataBuilder::buildQuoteData([], ['make_id' => 1, 'model_id' => 1]);
    $customMessage = 'Device quote created with custom message.';

    $mockService = Mockery::mock(DeviceQuoteService::class)->makePartial();
    $mockService->shouldReceive('create')
        ->once()
        ->andReturn((object) ['uuid' => $testUuid, 'message' => $customMessage]);

    $this->app->instance(DeviceQuoteService::class, $mockService);

    $response = $this->post(route('device-quotes-store'), $quoteData);

    $response->assertRedirect(route('device-quotes-show', $testUuid));
    $response->assertSessionHas('message', $customMessage);
});

test('validates first_name max length when creating device quote', function () {
    $quoteData = DeviceQuoteTestDataBuilder::buildQuoteData(
        ['first_name' => str_repeat('a', 21)],
        ['make_id' => 1, 'model_id' => 1]
    );

    $response = $this->post(route('device-quotes-store'), $quoteData);

    $response->assertSessionHasErrors(['first_name']);
    $response->assertStatus(302);
});

test('validates last_name max length when creating device quote', function () {
    $quoteData = DeviceQuoteTestDataBuilder::buildQuoteData(
        ['last_name' => str_repeat('a', 51)],
        ['make_id' => 1, 'model_id' => 1]
    );

    $response = $this->post(route('device-quotes-store'), $quoteData);

    $response->assertSessionHasErrors(['last_name']);
    $response->assertStatus(302);
});

test('validates email format when creating device quote', function () {
    $quoteData = DeviceQuoteTestDataBuilder::buildQuoteData(
        ['email' => 'not-an-email'],
        ['make_id' => 1, 'model_id' => 1]
    );

    $response = $this->post(route('device-quotes-store'), $quoteData);

    $response->assertSessionHasErrors(['email']);
    $response->assertStatus(302);
});

test('validates imei must be digits only when creating device quote', function () {
    $quoteData = DeviceQuoteTestDataBuilder::buildQuoteData(
        ['imei' => '12345678901234X'],
        ['make_id' => 1, 'model_id' => 1]
    );

    $response = $this->post(route('device-quotes-store'), $quoteData);

    $response->assertSessionHasErrors(['imei']);
    $response->assertStatus(302);
});

test('validates imei must be exactly 15 digits when creating device quote', function () {
    $quoteData = DeviceQuoteTestDataBuilder::buildQuoteData(
        ['imei' => '12345678901234'],
        ['make_id' => 1, 'model_id' => 1]
    );

    $response = $this->post(route('device-quotes-store'), $quoteData);

    $response->assertSessionHasErrors(['imei']);
    $response->assertStatus(302);
});
