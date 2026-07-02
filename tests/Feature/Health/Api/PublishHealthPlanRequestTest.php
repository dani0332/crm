<?php

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use App\Models\HealthRate;
use App\Models\HealthRateControl;
use App\Rules\HealthPlanRateControlPublishableRule;
use App\Rules\HealthPlanStatusValidRule;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

it('fails when health plan does not exist', function () {
    $failed = null;
    (new HealthPlanStatusValidRule('Only draft health plans can be published', [HealthPlanRateSheetStatusEnum::DRAFT->value]))
        ->validate('id', 999, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Health plan not found');
});

it('fails when health plan is not in draft status', function () {
    $plan = HealthPlan::factory()->active()->create();

    $failed = null;
    (new HealthPlanStatusValidRule('Only draft health plans can be published', [HealthPlanRateSheetStatusEnum::DRAFT->value]))
        ->validate('id', $plan->id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Only draft health plans can be published');
});

it('passes when health plan is in draft status', function () {
    $plan = HealthPlan::factory()->create();

    $failed = null;
    (new HealthPlanStatusValidRule('Only draft health plans can be published', [HealthPlanRateSheetStatusEnum::DRAFT->value]))
        ->validate('id', $plan->id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBeNull();
});

it('passes silently when health plan does not exist', function () {
    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', 999, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBeNull();
});

it('fails when health plan has no draft rate control', function () {
    $plan = HealthPlan::factory()->create();

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $plan->id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Health plan does not have a draft rate sheet.');
});

it('fails when draft rate control has no rates in draft status', function () {
    $plan = HealthPlan::factory()->create();
    HealthRateControl::factory()->create([
        'health_plan_id' => $plan->id,
        'effective_from' => now()->addDays(5)->toDateString(),
    ]);

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $plan->id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Rate sheet does not have any draft rates.');
});

it('fails when draft rate control effective_from is today', function () {
    $plan = HealthPlan::factory()->create();
    $control = HealthRateControl::factory()->effectiveToday()->create(['health_plan_id' => $plan->id]);
    HealthRate::factory()->create(['health_plan_id' => $plan->id, 'health_rate_control_id' => $control->id]);

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $plan->id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Effective from date must be greater than today.');
});

it('fails when draft rate control effective_from is in the past', function () {
    $plan = HealthPlan::factory()->create();
    $control = HealthRateControl::factory()->effectiveYesterday()->create(['health_plan_id' => $plan->id]);
    HealthRate::factory()->create(['health_plan_id' => $plan->id, 'health_rate_control_id' => $control->id]);

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $plan->id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Effective from date must be greater than today.');
});

it('fails when draft rate control effective_from is not greater than the active rate control', function () {
    $plan = HealthPlan::factory()->create();
    HealthRateControl::factory()->active()->create([
        'health_plan_id' => $plan->id,
        'effective_from' => now()->addDays(10)->toDateString(),
    ]);
    $control = HealthRateControl::factory()->create([
        'health_plan_id' => $plan->id,
        'effective_from' => now()->addDays(5)->toDateString(),
    ]);
    HealthRate::factory()->create(['health_plan_id' => $plan->id, 'health_rate_control_id' => $control->id]);

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $plan->id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBe('Effective from date must be greater than the existing active rate sheet effective from date.');
});

it('passes when draft rate control effective_from is after today and after the active rate control', function () {
    $plan = HealthPlan::factory()->create();
    HealthRateControl::factory()->active()->create([
        'health_plan_id' => $plan->id,
        'effective_from' => now()->addDays(5)->toDateString(),
    ]);
    $control = HealthRateControl::factory()->create([
        'health_plan_id' => $plan->id,
        'effective_from' => now()->addDays(10)->toDateString(),
    ]);
    HealthRate::factory()->create(['health_plan_id' => $plan->id, 'health_rate_control_id' => $control->id]);

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $plan->id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBeNull();
});

it('passes when draft rate control effective_from is after today and there is no active rate control', function () {
    $plan = HealthPlan::factory()->create();
    $control = HealthRateControl::factory()->create([
        'health_plan_id' => $plan->id,
        'effective_from' => now()->addDays(5)->toDateString(),
    ]);
    HealthRate::factory()->create(['health_plan_id' => $plan->id, 'health_rate_control_id' => $control->id]);

    $failed = null;
    (new HealthPlanRateControlPublishableRule)
        ->validate('id', $plan->id, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

    expect($failed)->toBeNull();
});
