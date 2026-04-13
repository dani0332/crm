<?php

use App\Enums\MotorRevivalEnum;

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
