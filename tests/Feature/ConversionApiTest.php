<?php

declare(strict_types=1);

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Events\LeadStatusUpdated;
use App\Events\PrivateClientUpdatedEvent;
use App\Events\QuotePolicyBooked;
use App\Facades\Capi;
use App\Listeners\TriggerConversionApis;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Services\BranchAssignmentService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
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

test('queues listener when QuotePolicyBooked event is dispatched', function () {
    $quoteUID = 'test-quote-uuid-123';
    $quoteTypeId = QuoteTypeId::Car;

    $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);
    event($event);

    Queue::assertPushed(function (CallQueuedListener $job) use ($quoteUID, $quoteTypeId) {
        return $job->class === TriggerConversionApis::class
            && $job->data[0]->quoteUID === $quoteUID
            && $job->data[0]->quoteTypeId === $quoteTypeId;
    });
});

test('queues listener with correct event data', function () {
    $quoteUID = 'test-quote-uuid-456';
    $quoteTypeId = QuoteTypeId::Car;

    $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);
    event($event);

    Queue::assertPushed(function (CallQueuedListener $job) use ($quoteUID, $quoteTypeId) {
        $eventData = $job->data[0];

        return $job->class === TriggerConversionApis::class
            && $eventData->quoteUID === $quoteUID
            && $eventData->quoteTypeId === $quoteTypeId
            && $eventData->eventType === 'Purchase';
    });
});

test('handles API failures gracefully without breaking the flow', function () {
    $quoteUID = 'test-quote-uuid-789';
    $quoteTypeId = QuoteTypeId::Car;

    $mockCapi = Mockery::mock('alias:'.Capi::class);
    $mockCapi->shouldReceive('request')
        ->andThrow(new Exception('API Error'));

    $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);

    // Should not throw exception
    expect(fn () => event($event))->not->toThrow(Exception::class);
});

test('handles network errors gracefully', function () {
    $quoteUID = 'test-quote-uuid-timeout';
    $quoteTypeId = QuoteTypeId::Car;

    $mockCapi = Mockery::mock('alias:'.Capi::class);
    $mockCapi->shouldReceive('request')
        ->andThrow(new Exception('Connection timeout'));

    $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);

    // Should not throw exception
    expect(fn () => event($event))->not->toThrow(Exception::class);
});

test('queues listener for different quote types', function () {
    $quoteTypes = [
        QuoteTypeId::Car => 'test-car-uuid',
        QuoteTypeId::Travel => 'test-travel-uuid',
        QuoteTypeId::Health => 'test-health-uuid',
    ];

    foreach ($quoteTypes as $quoteTypeId => $quoteUID) {
        $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);
        event($event);
    }

    // One queued listener per event for conversion API; QuotePolicyBooked also queues other listeners (e.g. Alfred Coins).
    expect(Queue::listenersPushed(TriggerConversionApis::class))->toHaveCount(count($quoteTypes));
});

test('queues listener with correct event structure for real example data', function () {
    $quoteUID = 'NREFC7RS';
    $quoteTypeId = 19;

    $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);
    event($event);

    Queue::assertPushed(function (CallQueuedListener $job) use ($quoteUID, $quoteTypeId) {
        $eventData = $job->data[0];

        return $job->class === TriggerConversionApis::class
            && $eventData->quoteUID === $quoteUID
            && $eventData->quoteTypeId === $quoteTypeId
            && $eventData->eventType === 'Purchase';
    });
});

test('queues listener with different quote type ID', function () {
    $quoteUID = '6UTE2JXU';
    $quoteTypeId = 1;

    $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);
    event($event);

    Queue::assertPushed(function (CallQueuedListener $job) use ($quoteUID, $quoteTypeId) {
        $eventData = $job->data[0];

        return $job->class === TriggerConversionApis::class
            && $eventData->quoteUID === $quoteUID
            && $eventData->quoteTypeId === $quoteTypeId
            && $eventData->eventType === 'Purchase';
    });
});

test('queues listener with dynamic quote type IDs', function () {
    $testCases = [
        ['quoteUID' => 'NREFC7RS', 'quoteTypeId' => 19],
        ['quoteUID' => '6UTE2JXU', 'quoteTypeId' => 1],
        ['quoteUID' => 'TEST123', 'quoteTypeId' => 5],
        ['quoteUID' => 'ABCD1234', 'quoteTypeId' => 10],
    ];

    foreach ($testCases as $testCase) {
        $event = new QuotePolicyBooked($testCase['quoteUID'], $testCase['quoteTypeId']);
        event($event);

        Queue::assertPushed(function (CallQueuedListener $job) use ($testCase) {
            $eventData = $job->data[0];

            return $job->class === TriggerConversionApis::class
                && $eventData->quoteUID === $testCase['quoteUID']
                && $eventData->quoteTypeId === $testCase['quoteTypeId']
                && $eventData->eventType === 'Purchase';
        });
    }
});
