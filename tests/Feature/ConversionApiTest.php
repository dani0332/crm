<?php

declare(strict_types=1);

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Events\LeadStatusUpdated;
use App\Events\PrivateClientUpdatedEvent;
use App\Events\QuotePolicyBooked;
use App\Facades\Capi;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Services\BranchAssignmentService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    Queue::fake();

    $branchAssignmentService = Mockery::mock(BranchAssignmentService::class);
    $branchAssignmentService->shouldReceive('saveBranchOverride')->andReturnNull();
    $branchAssignmentService->shouldReceive('getBranch')->andReturn(null);
    $this->instance(BranchAssignmentService::class, $branchAssignmentService);

    $policyIssuanceService = Mockery::mock(PolicyIssuanceService::class);
    $policyIssuanceService->shouldReceive('shouldValidateBranch')->andReturn(false);
    $this->instance(PolicyIssuanceService::class, $policyIssuanceService);

    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
});

test('dispatches QuotePolicyBooked event when car quote status changes to PolicyBooked', function () {
    Event::fake([
        QuotePolicyBooked::class,
        LeadStatusUpdated::class,
        PrivateClientUpdatedEvent::class,
    ]);

    $customer = Customer::factory()->create();

    $carQuote = CarQuote::factory()->create([
        'customer_id' => $customer->id,
        'quote_status_id' => QuoteStatusEnum::Quoted,
    ]);

    $carQuote->update([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
    ]);

    Event::assertDispatched(QuotePolicyBooked::class, function ($event) use ($carQuote) {
        return $event->quoteUID === $carQuote->uuid
            && $event->quoteTypeId === QuoteTypeId::Car;
    });
});

test('calls both Facebook and Google conversion APIs when QuotePolicyBooked event is dispatched', function () {
    $quoteUID = 'test-quote-uuid-123';
    $quoteTypeId = QuoteTypeId::Car;

    $mockCapi = Mockery::mock('alias:'.Capi::class);
    $mockCapi->shouldReceive('request')
        ->with('/api/v1-trigger-facebook-event-conversion', 'post', Mockery::on(function ($payload) use ($quoteUID, $quoteTypeId) {
            return $payload['quoteUID'] === $quoteUID
                && $payload['quoteTypeId'] === $quoteTypeId
                && $payload['eventType'] === 'Purchase';
        }))
        ->once()
        ->andReturn((object) ['success' => true]);

    $mockCapi->shouldReceive('request')
        ->with('/api/v1-trigger-google-event-conversion', 'post', Mockery::on(function ($payload) use ($quoteUID, $quoteTypeId) {
            return $payload['quoteUID'] === $quoteUID
                && $payload['quoteTypeId'] === $quoteTypeId
                && $payload['eventType'] === 'Purchase';
        }))
        ->once()
        ->andReturn((object) ['success' => true]);

    $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);
    event($event);
});

test('logs conversion API calls with UUID and eventType', function () {
    Log::spy();

    $quoteUID = 'test-quote-uuid-456';
    $quoteTypeId = QuoteTypeId::Car;

    $mockCapi = Mockery::mock('alias:'.Capi::class);
    $mockCapi->shouldReceive('request')
        ->andReturn((object) ['success' => true]);

    $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);
    event($event);

    Log::shouldHaveReceived('info')
        ->with(\Mockery::pattern('/ConversionApiService - Calling facebook conversion API/'), \Mockery::on(function ($context) use ($quoteUID) {
            return isset($context['uuid']) && $context['uuid'] === $quoteUID
                && isset($context['eventType']) && $context['eventType'] === 'Purchase'
                && isset($context['platform']) && $context['platform'] === 'facebook';
        }))
        ->once();

    Log::shouldHaveReceived('info')
        ->with(\Mockery::pattern('/ConversionApiService - Calling google conversion API/'), \Mockery::on(function ($context) use ($quoteUID) {
            return isset($context['uuid']) && $context['uuid'] === $quoteUID
                && isset($context['eventType']) && $context['eventType'] === 'Purchase'
                && isset($context['platform']) && $context['platform'] === 'google';
        }))
        ->once();
});

test('handles API failures gracefully without breaking the flow', function () {
    $quoteUID = 'test-quote-uuid-789';
    $quoteTypeId = QuoteTypeId::Car;

    $mockCapi = Mockery::mock('alias:'.Capi::class);
    $mockCapi->shouldReceive('request')
        ->andThrow(new \Exception('API Error'));

    $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);

    // Should not throw exception
    expect(fn () => event($event))->not->toThrow(Exception::class);
});

test('handles network errors gracefully', function () {
    $quoteUID = 'test-quote-uuid-timeout';
    $quoteTypeId = QuoteTypeId::Car;

    $mockCapi = Mockery::mock('alias:'.Capi::class);
    $mockCapi->shouldReceive('request')
        ->andThrow(new \Exception('Connection timeout'));

    $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);

    // Should not throw exception
    expect(fn () => event($event))->not->toThrow(Exception::class);
});

test('works with different quote types', function () {
    $quoteTypes = [
        QuoteTypeId::Car => 'test-car-uuid',
        QuoteTypeId::Travel => 'test-travel-uuid',
        QuoteTypeId::Health => 'test-health-uuid',
    ];

    $mockCapi = Mockery::mock('alias:'.Capi::class);
    $mockCapi->shouldReceive('request')
        ->andReturn((object) ['success' => true]);

    foreach ($quoteTypes as $quoteTypeId => $quoteUID) {
        $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);
        event($event);
    }

    // Verify that request was called for each quote type (2 APIs per quote = 6 total calls)
    $mockCapi->shouldHaveReceived('request')
        ->times(count($quoteTypes) * 2);
});

test('sends correct payload structure for Facebook conversion API with real example data', function () {
    $quoteUID = 'NREFC7RS';
    $quoteTypeId = 19;

    $mockCapi = Mockery::mock('alias:'.Capi::class);
    $mockCapi->shouldReceive('request')
        ->once()
        ->with('/api/v1-trigger-facebook-event-conversion', 'post', Mockery::on(function ($payload) use ($quoteUID, $quoteTypeId) {
            // Verify payload has exactly 3 keys
            expect(count($payload))->toBe(3);

            // Verify the exact structure
            expect($payload)->toHaveKeys(['quoteUID', 'quoteTypeId', 'eventType']);

            // Verify the values
            expect($payload['quoteUID'])->toBe($quoteUID);
            expect($payload['quoteTypeId'])->toBe($quoteTypeId);
            expect($payload['eventType'])->toBe('Purchase');

            return true;
        }))
        ->andReturn((object) ['success' => true]);

    $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);
    event($event);
});

test('sends correct payload structure for Google conversion API with real example data', function () {
    $quoteUID = '6UTE2JXU';
    $quoteTypeId = 1;

    $mockCapi = Mockery::mock('alias:'.Capi::class);
    $mockCapi->shouldReceive('request')
        ->once()
        ->with('/api/v1-trigger-google-event-conversion', 'post', Mockery::on(function ($payload) use ($quoteUID, $quoteTypeId) {
            // Verify payload has exactly 3 keys
            expect(count($payload))->toBe(3);

            // Verify the exact structure
            expect($payload)->toHaveKeys(['quoteUID', 'quoteTypeId', 'eventType']);

            // Verify the values
            expect($payload['quoteUID'])->toBe($quoteUID);
            expect($payload['quoteTypeId'])->toBe($quoteTypeId);
            expect($payload['eventType'])->toBe('Purchase');

            return true;
        }))
        ->andReturn((object) ['success' => true]);

    $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);
    event($event);
});

test('quote type ID remains dynamic across different quote types', function () {
    $testCases = [
        ['quoteUID' => 'NREFC7RS', 'quoteTypeId' => 19],
        ['quoteUID' => '6UTE2JXU', 'quoteTypeId' => 1],
        ['quoteUID' => 'TEST123', 'quoteTypeId' => 5],
        ['quoteUID' => 'ABCD1234', 'quoteTypeId' => 10],
    ];

    $mockCapi = Mockery::mock('alias:'.Capi::class);

    foreach ($testCases as $testCase) {
        $mockCapi->shouldReceive('request')
            ->with(Mockery::any(), 'post', Mockery::on(function ($payload) use ($testCase) {
                return $payload['quoteUID'] === $testCase['quoteUID']
                    && $payload['quoteTypeId'] === $testCase['quoteTypeId']
                    && $payload['eventType'] === 'Purchase';
            }))
            ->twice() // Once for Facebook, once for Google
            ->andReturn((object) ['success' => true]);

        $event = new QuotePolicyBooked($testCase['quoteUID'], $testCase['quoteTypeId']);
        event($event);
    }
});
