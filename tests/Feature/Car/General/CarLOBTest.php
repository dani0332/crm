<?php

use App\Models\CommunicationEventLog;
use App\Services\CommunicationEventLogService;
use Tests\Helpers\TestSchemaCreator;

// ============================================================================
// SECTION 1: COMMUNICATION EVENT LOG (CAR QUOTE)
// ============================================================================

beforeEach(function () {
    TestSchemaCreator::createCommunicationEventLogSchema();

    CommunicationEventLog::query()->delete();
});

test('returns communication event logs for a quote uuid ordered by created_at descending', function () {
    $uuid = 'QUOTE-UUID-1';

    CommunicationEventLog::factory()->create([
        'quote_uuid' => $uuid,
        'quote_type_id' => 1,
        'event_channel' => 'Email',
        'communication_type' => 'OCB Email',
        'action_event' => 'BUY_NOW',
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    CommunicationEventLog::factory()->create([
        'quote_uuid' => $uuid,
        'quote_type_id' => 1,
        'event_channel' => 'WhatsApp',
        'communication_type' => 'Follow-up',
        'action_event' => 'VIEW_PLANS',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $logs = app(CommunicationEventLogService::class)->getLogsForQuoteUuid($uuid);

    expect($logs)->toHaveCount(2)
        ->and($logs->first()->event_channel)->toBe('WhatsApp')
        ->and($logs->last()->event_channel)->toBe('Email');
});

test('returns an empty collection when no logs exist for the uuid', function () {
    $logs = app(CommunicationEventLogService::class)->getLogsForQuoteUuid('missing-uuid');

    expect($logs)->toBeEmpty();
});
