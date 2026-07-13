<?php

declare(strict_types=1);

use App\Jobs\GetQuotePlansJob;
use App\Models\HealthQuote;
use App\Models\Payment;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

test('serializing for the queue does not inline eager-loaded relation data', function () {
    $lead = HealthQuote::withoutEvents(fn () => HealthQuote::factory()->create([
        'code' => 'HEA-000123',
    ]));

    Payment::withoutEvents(fn () => Payment::factory()->create([
        'paymentable_id' => $lead->id,
        'paymentable_type' => HealthQuote::class,
        'code' => 'UNIQUE-PAYMENT-MARKER',
    ]));

    $lead->load('payments');

    $job = new GetQuotePlansJob($lead);

    $serialized = serialize($job);

    expect($serialized)->not->toContain('UNIQUE-PAYMENT-MARKER');

    $restored = unserialize($serialized);
    $property = new ReflectionProperty($restored, 'lead');
    $property->setAccessible(true);
    $restoredLead = $property->getValue($restored);

    expect($restoredLead)->toBeInstanceOf(HealthQuote::class)
        ->and($restoredLead->id)->toBe($lead->id)
        ->and($restoredLead->relationLoaded('payments'))->toBeTrue()
        ->and($restoredLead->payments->first()->code)->toBe('UNIQUE-PAYMENT-MARKER');
});
