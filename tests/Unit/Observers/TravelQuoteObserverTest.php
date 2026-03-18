<?php

declare(strict_types=1);

use App\Enums\QuoteJourneyEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Events\PrivateClientUpdatedEvent;
use App\Events\QuoteEmailUpdated;
use App\Events\QuotePolicyBooked;
use App\Jobs\CourtesyEmailJob;
use App\Models\QuoteJourney;
use App\Models\TravelQuote;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    Queue::fake();
    Event::fake([
        PrivateClientUpdatedEvent::class,
        QuoteEmailUpdated::class,
        QuotePolicyBooked::class,
    ]);
});

test('policy booked completes the policy issuance quote journey entry for travel quotes', function () {
    $travelQuote = TravelQuote::create([
        'uuid' => 'travel-quote-uuid',
        'code' => 'TRAVEL-001',
        'quote_status_id' => QuoteStatusEnum::Quoted,
        'source' => 'web',
    ]);

    $policyIssuanceEntry = QuoteJourney::create([
        'quote_uuid' => $travelQuote->uuid,
        'quote_type_id' => QuoteTypeId::Travel,
        'text' => 'Travel '.QuoteJourneyEnum::POLICY_ISSUANCE.' completed',
        'status' => QuoteJourneyEnum::PENDING,
    ]);

    $unrelatedEntry = QuoteJourney::create([
        'quote_uuid' => 'another-travel-quote-uuid',
        'quote_type_id' => QuoteTypeId::Travel,
        'text' => QuoteJourneyEnum::POLICY_ISSUANCE,
        'status' => QuoteJourneyEnum::PENDING,
    ]);

    $travelQuote->update([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
    ]);

    expect($policyIssuanceEntry->fresh()->status)->toBe(QuoteJourneyEnum::COMPLETED)
        ->and($unrelatedEntry->fresh()->status)->toBe(QuoteJourneyEnum::PENDING);

    Event::assertDispatched(PrivateClientUpdatedEvent::class);
    Event::assertDispatched(QuotePolicyBooked::class);
    Queue::assertPushed(CourtesyEmailJob::class);
});
