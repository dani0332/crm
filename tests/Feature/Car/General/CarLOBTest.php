<?php

use App\Enums\LeadSourceEnum;
use App\Enums\MotorRevivalEnum;
use App\Models\CarQuote;

// ============================================================================
// SECTION 1: MOTOR REVIVAL ENUM — ENGAGEMENT LEVEL LABELS (3 tests)
// ============================================================================

test('maps stored engagement_level values to business labels', function () {
    expect(MotorRevivalEnum::getEngagementLevelLabel(MotorRevivalEnum::COMMS_TRIGGERED->value))->toBe('Revival Comms Triggered')
        ->and(MotorRevivalEnum::getEngagementLevelLabel(MotorRevivalEnum::INTENT_LOW->value))->toBe('Revival Intent Low')
        ->and(MotorRevivalEnum::getEngagementLevelLabel(MotorRevivalEnum::MEDIUM_INTENT->value))->toBe('Revival Intent Medium')
        ->and(MotorRevivalEnum::getEngagementLevelLabel(MotorRevivalEnum::INTENT_HIGH->value))->toBe('Revival Intent High');
});

test('returns empty string for blank stored engagement_level values', function () {
    expect(MotorRevivalEnum::getEngagementLevelLabel(null))->toBe('')
        ->and(MotorRevivalEnum::getEngagementLevelLabel(''))->toBe('');
});

test('formats unknown engagement_level codes with a readable headline fallback', function () {
    expect(MotorRevivalEnum::getEngagementLevelLabel('Custom_Unknown_Value'))
        ->toBe('Custom Unknown Value');
});

// ============================================================================
// SECTION 2: isLeadSourceCar24 — CASE-INSENSITIVE SOURCE MATCHING (1 test)
// ============================================================================

test('matches car24 lead sources case-insensitively and rejects everything else', function (?string $source, bool $expected) {
    $quote = new CarQuote;
    $quote->source = $source;

    expect($quote->isLeadSourceCar24())->toBe($expected);
})->with([
    'exact match - CAR_24 url' => [LeadSourceEnum::CAR_24, true],
    'exact match - cars24' => ['cars24', true],
    'case-insensitive - CARS24 uppercase' => ['CARS24', true],
    'case-insensitive - Cars24 mixed case' => ['Cars24', true],
    'case-insensitive - CAR_24 url uppercased' => [strtoupper(LeadSourceEnum::CAR_24), true],
    'unrelated source' => ['web', false],
    'empty string' => ['', false],
    'null source' => [null, false],
    'partial match should fail' => ['cars2', false],
    'whitespace padded should fail' => [' cars24 ', false],
]);
