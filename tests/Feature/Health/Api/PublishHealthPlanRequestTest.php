<?php

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Rules\HealthPlanRateControlPublishableRule;
use App\Rules\HealthPlanStatusValidRule;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

// ============================================================================
// Helpers
// ============================================================================

function createPlan(string $status = 'draft'): int
{
    return DB::connection('sqlite')->table('health_plan')->insertGetId(['status' => $status]);
}

function createControl(int $planId, string $status, ?string $effectiveFrom = null): int
{
    return DB::connection('sqlite')->table('health_rates_control')->insertGetId([
        'health_plan_id' => $planId,
        'status' => $status,
        'effective_from' => $effectiveFrom,
    ]);
}

function createDraftRate(int $planId, int $controlId): void
{
    DB::connection('sqlite')->table('health_rates')->insert([
        'health_plan_id' => $planId,
        'health_rate_control_id' => $controlId,
        'status' => HealthPlanRateSheetStatusEnum::DRAFT->value,
    ]);
}

// ============================================================================
// HealthPlanStatusValidRule
// ============================================================================

it('fails when health plan does not exist', function () {
    $failed = null;
    (new HealthPlanStatusValidRule('Only draft health plans can be published', [HealthPlanRateSheetStatusEnum::DRAFT->value]))
        ->validate('id', 999, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Health plan not found');
});

it('fails when health plan is not in draft status', function () {
    $id = createPlan(HealthPlanRateSheetStatusEnum::ACTIVE->value);

    $failed = null;
    (new HealthPlanStatusValidRule('Only draft health plans can be published', [HealthPlanRateSheetStatusEnum::DRAFT->value]))
        ->validate('id', $id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Only draft health plans can be published');
});

it('passes when health plan is in draft status', function () {
    $id = createPlan(HealthPlanRateSheetStatusEnum::DRAFT->value);

    $failed = null;
    (new HealthPlanStatusValidRule('Only draft health plans can be published', [HealthPlanRateSheetStatusEnum::DRAFT->value]))
        ->validate('id', $id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBeNull();
});

// ============================================================================
// HealthPlanRateControlPublishableRule
// ============================================================================

it('passes silently when health plan does not exist', function () {
    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', 999, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBeNull();
});

it('fails when health plan has no draft rate control', function () {
    $id = createPlan();

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Health plan does not have a draft rate sheet.');
});

it('fails when draft rate control has no rates in draft status', function () {
    $id = createPlan();
    createControl($id, HealthPlanRateSheetStatusEnum::DRAFT->value, now()->addDays(5)->toDateString());

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Rate sheet does not have any draft rates.');
});

it('fails when draft rate control effective_from is today', function () {
    $id = createPlan();
    $controlId = createControl($id, HealthPlanRateSheetStatusEnum::DRAFT->value, now()->toDateString());
    createDraftRate($id, $controlId);

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Effective from date must be greater than today.');
});

it('fails when draft rate control effective_from is in the past', function () {
    $id = createPlan();
    $controlId = createControl($id, HealthPlanRateSheetStatusEnum::DRAFT->value, now()->subDay()->toDateString());
    createDraftRate($id, $controlId);

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Effective from date must be greater than today.');
});

it('fails when draft rate control effective_from is not greater than the active rate control', function () {
    $id = createPlan();
    createControl($id, HealthPlanRateSheetStatusEnum::ACTIVE->value, now()->addDays(10)->toDateString());
    $controlId = createControl($id, HealthPlanRateSheetStatusEnum::DRAFT->value, now()->addDays(5)->toDateString());
    createDraftRate($id, $controlId);

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Effective from date must be greater than the existing active rate sheet effective from date.');
});

it('passes when draft rate control effective_from is after today and after the active rate control', function () {
    $id = createPlan();
    createControl($id, HealthPlanRateSheetStatusEnum::ACTIVE->value, now()->addDays(5)->toDateString());
    $controlId = createControl($id, HealthPlanRateSheetStatusEnum::DRAFT->value, now()->addDays(10)->toDateString());
    createDraftRate($id, $controlId);

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBeNull();
});

it('passes when draft rate control effective_from is after today and there is no active rate control', function () {
    $id = createPlan();
    $controlId = createControl($id, HealthPlanRateSheetStatusEnum::DRAFT->value, now()->addDays(5)->toDateString());
    createDraftRate($id, $controlId);

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBeNull();
});
