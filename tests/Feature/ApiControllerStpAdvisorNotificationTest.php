<?php

use App\Enums\QuoteTypes;
use App\Http\Middleware\BasicAuth;
use App\Http\Requests\STPAdvisorNotificationRequest;
use App\Services\ApiService;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->withoutMiddleware(BasicAuth::class);
});

afterEach(function () {
    Mockery::close();
});

test('successfully sends STP advisor notification for valid health quote', function () {
    $advisor = TestDataSeeder::createUser([
        'email' => 'advisor@example.com',
        'name' => 'Test Advisor',
    ]);

    $quoteUuid = 'test-health-quote-uuid-'.uniqid();
    $db = DB::connection('sqlite');
    $quoteId = $db->table('health_quote_request')->insertGetId([
        'uuid' => $quoteUuid,
        'code' => 'TEST-'.uniqid(),
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'advisor_id' => $advisor->id,
        'quote_status_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $mockApiService = Mockery::mock(ApiService::class);
    $mockApiService->shouldReceive('stpAdvisorNotification')
        ->once()
        ->with(Mockery::on(function ($request) use ($quoteUuid) {
            return $request instanceof STPAdvisorNotificationRequest
                && $request->quoteUuid === $quoteUuid
                && $request->quoteTypeId === QuoteTypes::HEALTH->id();
        }))
        ->andReturn([
            'success' => true,
            'message' => 'STP Advisor notification sent',
        ]);

    $this->app->instance(ApiService::class, $mockApiService);

    $response = $this->postJson('/api/v1/stp-advisor-notification', [
        'quoteUuid' => $quoteUuid,
        'quoteTypeId' => QuoteTypes::HEALTH->id(),
        'apiFailed' => false,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'STP Advisor notification sent',
        ]);
});

test('returns error when lead not found', function () {
    $nonExistentUuid = 'non-existent-uuid-'.uniqid();

    $mockApiService = Mockery::mock(ApiService::class);
    $mockApiService->shouldReceive('stpAdvisorNotification')
        ->once()
        ->with(Mockery::on(function ($request) use ($nonExistentUuid) {
            return $request instanceof STPAdvisorNotificationRequest
                && $request->quoteUuid === $nonExistentUuid
                && $request->quoteTypeId === QuoteTypes::HEALTH->id();
        }))
        ->andReturn([
            'success' => false,
            'message' => 'Lead not found',
        ]);

    $this->app->instance(ApiService::class, $mockApiService);

    $response = $this->postJson('/api/v1/stp-advisor-notification', [
        'quoteUuid' => $nonExistentUuid,
        'quoteTypeId' => QuoteTypes::HEALTH->id(),
        'apiFailed' => false,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => false,
            'message' => 'Lead not found',
        ]);
});
