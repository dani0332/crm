<?php

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Jobs\Health\ActivateScheduledHealthPlansJob;
use App\Models\HealthPlan;
use App\Models\HealthRate;
use App\Models\HealthRateControl;
use Illuminate\Support\Facades\Log;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
    Log::spy();
});

// ============================================================================
// activateScheduledControls
// ============================================================================

it('activates a scheduled control whose effective_from is today', function () {
    $plan = HealthPlan::factory()->scheduled()->create();
    $control = HealthRateControl::factory()->scheduled()->effectiveToday()->create(['health_plan_id' => $plan->id]);
    $rate = HealthRate::factory()->scheduled()->create(['health_plan_id' => $plan->id, 'health_rate_control_id' => $control->id]);

    (new ActivateScheduledHealthPlansJob)->handle();

    expect($plan->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ACTIVE->value);
    expect($control->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ACTIVE->value);
    expect($rate->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ACTIVE->value);
});

it('does not activate a scheduled control whose effective_from is in the past', function () {
    $plan = HealthPlan::factory()->scheduled()->create();
    $control = HealthRateControl::factory()->scheduled()->effectiveYesterday()->create(['health_plan_id' => $plan->id]);

    (new ActivateScheduledHealthPlansJob)->handle();

    expect($plan->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::SCHEDULED->value);
    expect($control->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::SCHEDULED->value);
});

it('does not activate a scheduled control whose effective_from is in the future', function () {
    $plan = HealthPlan::factory()->scheduled()->create();
    $control = HealthRateControl::factory()->scheduled()->create(['health_plan_id' => $plan->id]);

    (new ActivateScheduledHealthPlansJob)->handle();

    expect($plan->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::SCHEDULED->value);
    expect($control->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::SCHEDULED->value);
});

it('bumps the plan version from the rate control version when the plan is in scheduled status', function () {
    $plan = HealthPlan::factory()->scheduled()->create(['version' => 1.0]);
    $control = HealthRateControl::factory()->scheduled()->effectiveToday()->create(['health_plan_id' => $plan->id, 'version' => 1.3]);
    HealthRate::factory()->scheduled()->create(['health_plan_id' => $plan->id, 'health_rate_control_id' => $control->id]);

    (new ActivateScheduledHealthPlansJob)->handle();

    expect((float) $plan->fresh()->version)->toBe(2.0);
    expect((float) $control->fresh()->version)->toBe(2.0);
});

it('does not bump the plan version when the plan is not in scheduled status', function () {
    $plan = HealthPlan::factory()->archived()->create(['version' => 1.3]);
    $control = HealthRateControl::factory()->scheduled()->effectiveToday()->create(['health_plan_id' => $plan->id, 'version' => 1.3]);
    HealthRate::factory()->create(['health_plan_id' => $plan->id, 'health_rate_control_id' => $control->id]);

    (new ActivateScheduledHealthPlansJob)->handle();

    expect((float) $plan->fresh()->version)->toBe(1.3);
    expect($plan->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ACTIVE->value);
});

it('archives the active sibling plan with the same code when activating', function () {
    $activePlan = HealthPlan::factory()->active()->create(['code' => 'PLAN-001', 'version' => 1.0]);
    $scheduledPlan = HealthPlan::factory()->scheduled()->create(['code' => 'PLAN-001', 'version' => 1.1]);
    $control = HealthRateControl::factory()->scheduled()->effectiveToday()->create(['health_plan_id' => $scheduledPlan->id]);
    HealthRate::factory()->scheduled()->create(['health_plan_id' => $scheduledPlan->id, 'health_rate_control_id' => $control->id]);

    (new ActivateScheduledHealthPlansJob)->handle();

    expect($activePlan->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ARCHIVED->value);
    expect($scheduledPlan->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ACTIVE->value);
});

it('archives the active control of the sibling plan when activating', function () {
    $activePlan = HealthPlan::factory()->active()->create(['code' => 'PLAN-001']);
    $oldControl = HealthRateControl::factory()->active()->create([
        'health_plan_id' => $activePlan->id,
        'effective_from' => now()->subMonth()->toDateString(),
    ]);
    HealthRate::factory()->active()->create(['health_plan_id' => $activePlan->id, 'health_rate_control_id' => $oldControl->id]);

    $scheduledPlan = HealthPlan::factory()->scheduled()->create(['code' => 'PLAN-001']);
    $newControl = HealthRateControl::factory()->scheduled()->effectiveToday()->create(['health_plan_id' => $scheduledPlan->id]);
    HealthRate::factory()->scheduled()->create(['health_plan_id' => $scheduledPlan->id, 'health_rate_control_id' => $newControl->id]);

    (new ActivateScheduledHealthPlansJob)->handle();

    expect($oldControl->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ARCHIVED->value);
    expect($newControl->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ACTIVE->value);
});

it('skips a rate control with no associated plan', function () {
    HealthRateControl::factory()->scheduled()->effectiveToday()->create(['health_plan_id' => 9999]);

    expect(fn () => (new ActivateScheduledHealthPlansJob)->handle())->not->toThrow(Exception::class);
});

// ============================================================================
// archiveExpiredControls
// ============================================================================

it('archives controls and their plan when effective_to has passed', function () {
    $plan = HealthPlan::factory()->active()->create();
    $control = HealthRateControl::factory()->active()->create([
        'health_plan_id' => $plan->id,
        'effective_from' => now()->subMonth()->toDateString(),
        'effective_to' => now()->subDay()->toDateString(),
    ]);
    $rate = HealthRate::factory()->active()->create(['health_plan_id' => $plan->id, 'health_rate_control_id' => $control->id]);

    (new ActivateScheduledHealthPlansJob)->handle();

    expect($plan->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ARCHIVED->value);
    expect($control->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ARCHIVED->value);
    expect($rate->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ARCHIVED->value);
});

it('does not archive a control whose effective_to is today', function () {
    $plan = HealthPlan::factory()->active()->create();
    $control = HealthRateControl::factory()->active()->create([
        'health_plan_id' => $plan->id,
        'effective_from' => now()->subMonth()->toDateString(),
        'effective_to' => now()->toDateString(),
    ]);

    (new ActivateScheduledHealthPlansJob)->handle();

    expect($control->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ACTIVE->value);
});

it('does nothing when there are no scheduled or expired controls', function () {
    $plan = HealthPlan::factory()->active()->create();
    $control = HealthRateControl::factory()->active()->create([
        'health_plan_id' => $plan->id,
        'effective_from' => now()->subMonth()->toDateString(),
        'effective_to' => now()->addMonth()->toDateString(),
    ]);

    (new ActivateScheduledHealthPlansJob)->handle();

    expect($plan->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ACTIVE->value);
    expect($control->fresh()->status)->toBe(HealthPlanRateSheetStatusEnum::ACTIVE->value);
});
